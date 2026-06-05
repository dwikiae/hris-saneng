<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeNote;
use App\Models\EmployeeOffboarding;
use App\Models\OffboardingChecklistItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeOffboardingUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeOffboardingEmployee($this->company, $this->user);
});

it('initiates offboarding and creates a system note', function () {
    $response = $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding", [
        'reason_type' => EmployeeOffboarding::REASON_RESIGNATION,
        'reason_detail' => 'Mengundurkan diri.',
        'last_working_date' => '2026-07-31',
        'notes' => 'Exit interview required.',
        'status' => EmployeeOffboarding::STATUS_COMPLETED,
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.offboarding.created')
        ->assertJsonPath('data.status', EmployeeOffboarding::STATUS_DRAFT)
        ->assertJsonPath('data.initiated_by', $this->user->id);

    $offboardingId = $response->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/offboarding")
        ->assertOk()
        ->assertJsonPath('data.is_visible', true)
        ->assertJsonPath('data.offboarding.id', $offboardingId);

    expect(EmployeeNote::where('employee_id', $this->employee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Offboarding dimulai: resignation - Mengundurkan diri.')
        ->exists())->toBeTrue();
});

it('prevents two active offboardings for one employee', function () {
    $payload = [
        'reason_type' => EmployeeOffboarding::REASON_OTHER,
        'last_working_date' => '2026-07-31',
    ];

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding", $payload)
        ->assertCreated();

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'employee.offboarding.active_exists');

    expect(EmployeeOffboarding::where('employee_id', $this->employee->id)->count())->toBe(1);
});

it('fails completion when checklist is incomplete', function () {
    $offboarding = employeeOffboardingRecord($this->company, $this->employee, $this->user);
    OffboardingChecklistItem::create([
        'company_id' => $this->company->id,
        'offboarding_id' => $offboarding->id,
        'title' => 'Return laptop',
        'is_completed' => false,
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding/{$offboarding->id}/complete")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'employee.offboarding.incomplete_checklist')
        ->assertJsonPath('data.incomplete_checklist_items.0.title', 'Return laptop');

    expect($offboarding->refresh()->status)->toBe(EmployeeOffboarding::STATUS_DRAFT);
});

it('completes offboarding and inactivates employee while superseding active contracts', function () {
    $offboarding = employeeOffboardingRecord($this->company, $this->employee, $this->user, [
        'reason_type' => EmployeeOffboarding::REASON_CONTRACT_END,
        'last_working_date' => '2026-07-31',
    ]);
    $checklist = OffboardingChecklistItem::create([
        'company_id' => $this->company->id,
        'offboarding_id' => $offboarding->id,
        'title' => 'Return access card',
        'is_completed' => true,
        'completed_by' => $this->user->id,
        'completed_at' => now(),
    ]);
    $contract = EmployeeContract::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'contract_type' => EmployeeContract::TYPE_PKWTT,
        'contract_number' => 'CTR-OFF-001',
        'start_date' => '2026-01-01',
        'status' => EmployeeContract::STATUS_ACTIVE,
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding/{$offboarding->id}/complete")
        ->assertOk()
        ->assertJsonPath('message', 'employee.offboarding.completed')
        ->assertJsonPath('data.status', EmployeeOffboarding::STATUS_COMPLETED)
        ->assertJsonPath('data.completed_by', $this->user->id);

    expect(Employee::findOrFail($this->employee->id)->status)->toBe(Employee::INACTIVE);
    expect(EmployeeContract::withArchived()->findOrFail($contract->id)->status)
        ->toBe(EmployeeContract::STATUS_SUPERSEDED);
    expect(EmployeeContract::withArchived()->findOrFail($contract->id)->archived_at)->not->toBeNull();
    expect($checklist->refresh()->is_completed)->toBeTrue();
    expect(EmployeeNote::where('employee_id', $this->employee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Offboarding selesai: contract_end')
        ->exists())->toBeTrue();
});

it('records termination detail in completion system note', function () {
    $offboarding = employeeOffboardingRecord($this->company, $this->employee, $this->user, [
        'reason_type' => EmployeeOffboarding::REASON_TERMINATION,
        'reason_detail' => 'Pelanggaran berat sesuai keputusan HR.',
        'last_working_date' => '2026-08-15',
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding/{$offboarding->id}/complete")
        ->assertOk();

    expect(EmployeeNote::where('employee_id', $this->employee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Offboarding selesai: termination - Detail: Pelanggaran berat sesuai keputusan HR. - Tanggal kerja terakhir: 2026-08-15')
        ->exists())->toBeTrue();
});

function employeeOffboardingUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Offboarding Admin',
        'email' => 'employee.offboarding.admin@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => 'system_admin',
        'name' => 'System Admin',
    ]);

    $user->roles()->attach($role->id);

    return $user;
}

function employeeOffboardingEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-OFF-001',
        'name' => 'Offboarding Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}

function employeeOffboardingRecord(Company $company, Employee $employee, User $user, array $overrides = []): EmployeeOffboarding
{
    return EmployeeOffboarding::create(array_merge([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'reason_type' => EmployeeOffboarding::REASON_RESIGNATION,
        'last_working_date' => '2026-07-31',
        'status' => EmployeeOffboarding::STATUS_DRAFT,
        'initiated_by' => $user->id,
        'initiated_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ], $overrides));
}
