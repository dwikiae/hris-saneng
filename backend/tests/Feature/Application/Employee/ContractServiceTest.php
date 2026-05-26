<?php

use App\Application\Employee\CreateContractService;
use App\Application\Employee\RenewContractService;
use App\Application\Employee\TerminateContractService;
use App\Application\Employee\UpdateContractService;
use App\Domain\Employee\Exceptions\ContractOverlapException;
use App\Jobs\Employee\SendContractExpiryNotificationJob;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('rejects PKWT contract without end date', function () {
    $fixture = contractServiceFixture(['contract.create']);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(CreateContractService::class)->execute($fixture['employee'], contractServicePayload($fixture, [
        'end_date' => null,
        'status' => Contract::ACTIVE,
    ])))->toThrow(ValidationException::class);
});

it('rejects overlapping active contract', function () {
    $fixture = contractServiceFixture(['contract.create']);

    contractServiceContract($fixture, [
        'contract_number' => 'CTR-2026-0001',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => Contract::ACTIVE,
    ]);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(CreateContractService::class)->execute($fixture['employee'], contractServicePayload($fixture, [
        'contract_number' => 'CTR-2026-0002',
        'start_date' => '2026-12-31',
        'end_date' => '2027-12-31',
        'status' => Contract::ACTIVE,
    ])))->toThrow(ContractOverlapException::class);
});

it('creates next active contract and expires previous active contract', function () {
    $fixture = contractServiceFixture(['contract.create']);

    $old = contractServiceContract($fixture, [
        'contract_number' => 'CTR-2026-0001',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => Contract::ACTIVE,
    ]);

    $this->actingAs($fixture['actor']);

    $new = app(CreateContractService::class)->execute($fixture['employee'], contractServicePayload($fixture, [
        'contract_number' => 'CTR-2026-0002',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'status' => Contract::ACTIVE,
    ]));

    expect($new->status)->toBe(Contract::ACTIVE)
        ->and($old->refresh()->status)->toBe(Contract::EXPIRED)
        ->and($fixture['employee']->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();
});

it('renews active PKWT contract from old end date plus one day', function () {
    $fixture = contractServiceFixture(['contract.create']);
    $old = contractServiceContract($fixture, [
        'contract_number' => 'CTR-2026-0001',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => Contract::ACTIVE,
    ])->load('contractType');

    $this->actingAs($fixture['actor']);

    $renewed = app(RenewContractService::class)->execute($fixture['employee'], $old, [
        'contract_number' => 'CTR-2027-0001',
        'end_date' => '2027-12-31',
        'document_path' => 'employees/1/documents/kontrak/renewal.pdf',
    ]);

    expect($renewed->start_date->toDateString())->toBe('2027-01-01')
        ->and($renewed->contract_type_id)->toBe($old->contract_type_id)
        ->and($old->refresh()->status)->toBe(Contract::EXPIRED);
});

it('terminates active contract', function () {
    $fixture = contractServiceFixture(['contract.terminate']);
    $contract = contractServiceContract($fixture, [
        'status' => Contract::ACTIVE,
    ]);

    $this->actingAs($fixture['actor']);

    $terminated = app(TerminateContractService::class)->execute($contract, [
        'termination_reason' => 'employee_offboarded',
        'termination_date' => '2026-06-30',
        'termination_notes' => 'Completed offboarding.',
    ]);

    expect($terminated->status)->toBe(Contract::TERMINATED)
        ->and($terminated->termination_date->toDateString())->toBe('2026-06-30')
        ->and($fixture['employee']->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();
});

it('rejects editing active contract', function () {
    $fixture = contractServiceFixture(['contract.create']);
    $contract = contractServiceContract($fixture, [
        'status' => Contract::ACTIVE,
    ]);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(UpdateContractService::class)->execute($contract, [
        'document_path' => 'employees/1/documents/kontrak/correction.pdf',
    ]))->toThrow(AuthorizationException::class);
});

it('updates draft contract', function () {
    $fixture = contractServiceFixture(['contract.create']);
    $contract = contractServiceContract($fixture);

    $this->actingAs($fixture['actor']);

    $updated = app(UpdateContractService::class)->execute($contract, [
        'document_path' => 'employees/1/documents/kontrak/draft.pdf',
    ]);

    expect($updated->document_path)->toBe('employees/1/documents/kontrak/draft.pdf');
});

it('queues expiry notification jobs from command', function () {
    Queue::fake();
    $fixture = contractServiceFixture(['contract.view']);
    contractServiceContract($fixture, [
        'status' => Contract::ACTIVE,
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
    ]);

    expect(app(ContractRepositoryInterface::class)->listActiveExpiringOnDates([
        now()->addDays(30)->toDateString(),
    ]))->toHaveCount(1);

    Artisan::call('employee:check-contract-expiry');

    Queue::assertPushed(SendContractExpiryNotificationJob::class);
});

/**
 * @param  list<string>  $permissions
 * @return array<string, mixed>
 */
function contractServiceFixture(array $permissions): array
{
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $actor = contractServiceUser($company, 'contract_actor', $permissions);
    $department = Department::create(['company_id' => $company->id, 'code' => 'HRD', 'name' => 'HRD']);
    $position = Position::create(['company_id' => $company->id, 'code' => 'STAFF', 'name' => 'Staff']);
    $employmentType = EmploymentType::create(['company_id' => $company->id, 'code' => 'PKWT', 'name' => 'PKWT']);
    $contractType = ContractType::create([
        'company_id' => $company->id,
        'code' => 'PKWT',
        'name' => 'PKWT',
        'requires_end_date' => true,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'company_id' => $company->id,
        'full_name' => 'Contract Employee',
        'name' => 'Contract Employee',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_type_id' => $employmentType->id,
        'nik' => '3374010101010001',
        'consent_at' => now(),
        'consent_by' => $actor->id,
        'consent_text' => 'Consent text',
        'status' => Employee::ACTIVE,
    ]);

    return compact('company', 'actor', 'department', 'position', 'employmentType', 'contractType', 'employee');
}

/**
 * @param  list<string>  $permissions
 */
function contractServiceUser(Company $company, string $roleCode, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => $roleCode,
        'email' => $roleCode.'@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create(['company_id' => $company->id, 'code' => $roleCode, 'name' => $roleCode]);

    foreach ($permissions as $code) {
        [$module, $action] = explode('.', $code, 2);
        $permission = Permission::firstOrCreate(
            ['company_id' => $company->id, 'module' => $module, 'action' => $action],
            ['code' => $code, 'name' => $code]
        );
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contractServicePayload(array $fixture, array $overrides = []): array
{
    return array_merge([
        'contract_type_id' => $fixture['contractType']->id,
        'position_id' => $fixture['position']->id,
        'department_id' => $fixture['department']->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $overrides
 */
function contractServiceContract(array $fixture, array $overrides = []): Contract
{
    return Contract::create(array_merge([
        'company_id' => $fixture['company']->id,
        'employee_id' => $fixture['employee']->id,
        'contract_number' => 'CTR-2026-9999',
        'contract_type_id' => $fixture['contractType']->id,
        'position_id' => $fixture['position']->id,
        'department_id' => $fixture['department']->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => Contract::DRAFT,
    ], $overrides));
}
