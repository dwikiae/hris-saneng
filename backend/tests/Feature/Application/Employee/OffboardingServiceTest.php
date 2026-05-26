<?php

use App\Application\Employee\FinalizeOffboardingService;
use App\Application\Employee\StartOffboardingService;
use App\Application\Employee\UpdateOffboardingItemService;
use App\Domain\Employee\Exceptions\IncompleteChecklistException;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\OffboardingTemplate;
use App\Models\OffboardingTemplateItem;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\TerminationReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('starts offboarding and snapshots template checklist', function () {
    $fixture = offboardingFixture();

    $this->actingAs($fixture['actor']);

    $record = app(StartOffboardingService::class)->execute($fixture['employee'], [
        'termination_reason_id' => $fixture['reason']->id,
        'termination_date' => '2026-07-31',
        'notes' => 'Resign.',
    ]);

    expect($record->items)->toHaveCount(2)
        ->and($fixture['employee']->refresh()->offboarding_started_at)->not->toBeNull()
        ->and($fixture['employee']->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();
});

it('rejects finalize while mandatory checklist is incomplete', function () {
    $fixture = offboardingFixture();
    $this->actingAs($fixture['actor']);
    $record = app(StartOffboardingService::class)->execute($fixture['employee'], [
        'termination_reason_id' => $fixture['reason']->id,
        'termination_date' => '2026-07-31',
    ]);

    expect(fn () => app(FinalizeOffboardingService::class)->execute($fixture['employee'], $record->id))
        ->toThrow(IncompleteChecklistException::class);
});

it('finalizes offboarding atomically after mandatory checklist is done', function () {
    $fixture = offboardingFixture();
    $this->actingAs($fixture['actor']);
    $record = app(StartOffboardingService::class)->execute($fixture['employee'], [
        'termination_reason_id' => $fixture['reason']->id,
        'termination_date' => '2026-07-31',
    ]);

    foreach ($record->items as $item) {
        if ($item->is_mandatory) {
            app(UpdateOffboardingItemService::class)->execute($record->id, $item->id, true);
        }
    }

    app(FinalizeOffboardingService::class)->execute($fixture['employee'], $record->id);

    $employee = Employee::withArchived()->findOrFail($fixture['employee']->id);

    expect($employee->status)->toBe(Employee::ARCHIVED)
        ->and($employee->end_date->toDateString())->toBe('2026-07-31')
        ->and($employee->archived_at)->not->toBeNull()
        ->and($fixture['contract']->refresh()->status)->toBe(Contract::TERMINATED)
        ->and($fixture['linkedUser']->refresh()->active)->toBeFalse()
        ->and($record->refresh()->status)->toBe('completed');
});

/**
 * @return array<string, mixed>
 */
function offboardingFixture(): array
{
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $actor = offboardingUser($company, 'offboarding_actor', ['offboarding.manage']);
    $department = Department::create(['company_id' => $company->id, 'code' => 'HRD', 'name' => 'HRD']);
    $position = Position::create(['company_id' => $company->id, 'code' => 'STAFF', 'name' => 'Staff']);
    $employmentType = EmploymentType::create(['company_id' => $company->id, 'code' => 'PKWT', 'name' => 'PKWT']);
    $reason = TerminationReason::create(['company_id' => $company->id, 'code' => 'resign', 'name' => 'Resign']);

    $template = OffboardingTemplate::create([
        'company_id' => $company->id,
        'termination_reason_id' => $reason->id,
        'name' => 'Resign Checklist',
    ]);
    OffboardingTemplateItem::create([
        'company_id' => $company->id,
        'template_id' => $template->id,
        'item_name' => 'Return ID card',
        'department_responsible' => 'HRD',
        'is_mandatory' => true,
        'sort_order' => 1,
    ]);
    OffboardingTemplateItem::create([
        'company_id' => $company->id,
        'template_id' => $template->id,
        'item_name' => 'Exit interview',
        'department_responsible' => 'HRD',
        'is_mandatory' => false,
        'sort_order' => 2,
    ]);

    $employee = Employee::create([
        'company_id' => $company->id,
        'full_name' => 'Offboard Employee',
        'name' => 'Offboard Employee',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_type_id' => $employmentType->id,
        'nik' => '3374010101010001',
        'consent_at' => now(),
        'consent_by' => $actor->id,
        'consent_text' => 'Consent text',
        'status' => Employee::ACTIVE,
    ]);
    $linkedUser = User::create([
        'company_id' => $company->id,
        'name' => 'Linked User',
        'email' => 'linked-user@example.test',
        'password' => 'password',
        'employee_id' => $employee->id,
    ]);
    $employee->forceFill(['user_id' => $linkedUser->id])->save();
    $contractType = ContractType::create([
        'company_id' => $company->id,
        'code' => 'PKWT',
        'name' => 'PKWT',
        'requires_end_date' => true,
    ]);
    $contract = Contract::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'contract_number' => 'CTR-2026-0001',
        'contract_type_id' => $contractType->id,
        'position_id' => $position->id,
        'department_id' => $department->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => Contract::ACTIVE,
    ]);

    return compact('company', 'actor', 'employee', 'linkedUser', 'reason', 'contract');
}

/**
 * @param  list<string>  $permissions
 */
function offboardingUser(Company $company, string $roleCode, array $permissions): User
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
        $permission = Permission::create([
            'company_id' => $company->id,
            'code' => $code,
            'module' => $module,
            'action' => $action,
            'name' => $code,
        ]);
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}
