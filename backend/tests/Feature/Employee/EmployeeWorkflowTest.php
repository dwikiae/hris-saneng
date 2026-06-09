<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Modules\Karyawan\Models\EmployeeModuleSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);

    $this->department = Department::create([
        'company_id' => $this->company->id,
        'code' => 'HRD',
        'name' => 'HRD',
        'is_active' => true,
    ]);

    $this->otherDepartment = Department::create([
        'company_id' => $this->company->id,
        'code' => 'OPERASIONAL',
        'name' => 'Operasional',
        'is_active' => true,
    ]);

    $this->position = Position::create([
        'company_id' => $this->company->id,
        'code' => 'STAFF',
        'name' => 'Staff',
        'is_active' => true,
    ]);

    $this->employmentType = EmploymentType::create([
        'company_id' => $this->company->id,
        'code' => 'TETAP',
        'name' => 'Tetap',
        'is_active' => true,
    ]);

    $this->permissions = collect([
        'employee.view',
        'employee.create',
        'employee.update',
        'employee.archive',
        'employee.approve',
        'employee.override_number',
        'employee.view_sensitive',
        'employee.view_salary',
    ])->mapWithKeys(fn (string $code): array => [$code => employeeWorkflowPermission($this->company, $code)]);
});

it('runs employee approval workflow end to end', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_workflow', $this->permissions->values()->all());

    $this->actingAs($actor);

    $createResponse = $this->postJson('/api/v1/employees', employeeWorkflowPayload($this))
        ->assertCreated()
        ->assertJsonPath('message', 'employee.created')
        ->assertJsonPath('data.status', Employee::DRAFT);

    $employeeId = $createResponse->json('data.id');

    expect($createResponse->json('data.employee_number'))->toBe('EMP-001');
    expect(EmployeeContract::where('employee_id', $employeeId)->where('status', EmployeeContract::STATUS_DRAFT)->exists())->toBeTrue();
    expect(EmployeeEmergencyContact::where('employee_id', $employeeId)->where('name', 'Emergency Contact')->exists())->toBeTrue();

    $this->postJson("/api/v1/employees/{$employeeId}/submit")
        ->assertOk()
        ->assertJsonPath('message', 'employee.submitted')
        ->assertJsonPath('data.status', Employee::PENDING);

    $this->postJson("/api/v1/employees/{$employeeId}/approve")
        ->assertOk()
        ->assertJsonPath('message', 'employee.approved')
        ->assertJsonPath('data.status', Employee::ACTIVE)
        ->assertJsonPath('data.approved_by', $actor->id);

    expect(EmployeeContract::where('employee_id', $employeeId)->where('status', EmployeeContract::STATUS_ACTIVE)->exists())->toBeTrue();

    $rejectResponse = $this->postJson('/api/v1/employees', employeeWorkflowPayload($this, [
        'email' => 'workflow.reject@saneng.co.id',
        'nik' => '3374010101010002',
    ]))->assertCreated();

    $rejectEmployeeId = $rejectResponse->json('data.id');

    $this->postJson("/api/v1/employees/{$rejectEmployeeId}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', Employee::PENDING);

    $this->postJson("/api/v1/employees/{$rejectEmployeeId}/reject", [
        'reason' => 'Need supporting document.',
    ])->assertOk()
        ->assertJsonPath('message', 'employee.rejected')
        ->assertJsonPath('data.status', Employee::DRAFT)
        ->assertJsonPath('data.rejection_reason', 'Need supporting document.');
});

it('rejects invalid approval transition from draft', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_invalid_transition', $this->permissions->values()->all());
    $employee = employeeWorkflowEmployee($this, ['status' => Employee::DRAFT]);

    $this->actingAs($actor)
        ->postJson("/api/v1/employees/{$employee->id}/approve")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'employee.invalid_status_transition');
});

it('auto fills consent when creating employee', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_consent', $this->permissions->values()->all());
    $payload = employeeWorkflowPayload($this);
    unset($payload['consent_at']);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', $payload)
        ->assertCreated()
        ->assertJsonPath('data.consent_by', $actor->id);

    expect(Employee::firstOrFail()->consent_at)->not->toBeNull();
});

it('generates employee number from department join date and sequence tokens', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_token_number', $this->permissions->values()->all());
    EmployeeModuleSetting::create([
        'company_id' => $this->company->id,
        'key' => 'employee_number_format',
        'value' => '{DEPT_CODE}-{JOIN:YYYY}-{SEQ:4}',
    ]);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', employeeWorkflowPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.employee_number', 'HRD-2024-0001');
});

it('returns clear validation error when required token data is missing', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_missing_token_data', $this->permissions->values()->all());
    EmployeeModuleSetting::create([
        'company_id' => $this->company->id,
        'key' => 'employee_number_format',
        'value' => '{CONTRACT_TYPE}-{SEQ:3}',
    ]);
    $payload = employeeWorkflowPayload($this);
    unset($payload['contract'], $payload['contract_type']);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Contract type is required for this employee number format.');
});

it('keeps legacy sequence token format compatible', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_legacy_number', $this->permissions->values()->all());
    $this->travelTo(now()->setDate(2026, 6, 9));
    EmployeeModuleSetting::create([
        'company_id' => $this->company->id,
        'key' => 'employee_number_format',
        'value' => 'EMP-{YYYY}-{SEQ4}',
    ]);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', employeeWorkflowPayload($this))
        ->assertCreated()
        ->assertJsonPath('data.employee_number', 'EMP-2026-0001');
});

it('uses highest existing employee number including archived rows for the next sequence', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_sequence', $this->permissions->values()->all());
    $archived = employeeWorkflowEmployee($this, [
        'employee_number' => 'EMP-009',
        'archived_at' => now(),
        'archived_by' => $actor->id,
    ]);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', employeeWorkflowPayload($this, [
            'nik' => '3374010101010010',
            'email' => 'sequence.employee@saneng.co.id',
        ]))
        ->assertCreated()
        ->assertJsonPath('data.employee_number', 'EMP-010');

    expect(Employee::withArchived()->findOrFail($archived->id)->employee_number)->toBe('EMP-009');
});

it('locks generated employee number unless actor has override permission', function () {
    $withoutOverride = employeeWorkflowUser($this->company, 'hr_without_override', [
        $this->permissions->get('employee.view'),
        $this->permissions->get('employee.update'),
    ]);
    $withOverride = employeeWorkflowUser($this->company, 'hr_with_override', [
        $this->permissions->get('employee.view'),
        $this->permissions->get('employee.update'),
        $this->permissions->get('employee.override_number'),
    ]);
    $employee = employeeWorkflowEmployee($this, ['employee_number' => 'EMP-020']);

    $this->actingAs($withoutOverride)
        ->putJson("/api/v1/employees/{$employee->id}", ['employee_number' => 'EMP-021'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'employee.employee_number_locked');

    $this->actingAs($withOverride)
        ->putJson("/api/v1/employees/{$employee->id}", ['employee_number' => 'EMP-021'])
        ->assertOk()
        ->assertJsonPath('data.employee_number', 'EMP-021');
});

it('archives employee and removes it from index', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_archive', $this->permissions->values()->all());
    $employee = employeeWorkflowEmployee($this, [
        'employee_number' => 'EMP-ARCH-001',
        'name' => 'Archive Target',
        'status' => Employee::ACTIVE,
    ]);

    $this->actingAs($actor)
        ->postJson("/api/v1/employees/{$employee->id}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'employee.archived');

    expect(Employee::withArchived()->findOrFail($employee->id)->archived_at)->not->toBeNull();

    $this->getJson('/api/v1/employees')
        ->assertOk()
        ->assertJsonMissing(['employee_number' => 'EMP-ARCH-001']);
});

it('forbids creating employee without employee create permission', function () {
    $actor = employeeWorkflowUser($this->company, 'viewer_without_create', [
        $this->permissions->get('employee.view'),
    ]);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', employeeWorkflowPayload($this))
        ->assertForbidden();
});

it('forbids approving employee without employee approve permission', function () {
    $actor = employeeWorkflowUser($this->company, 'updater_without_approve', [
        $this->permissions->get('employee.view'),
        $this->permissions->get('employee.update'),
    ]);
    $employee = employeeWorkflowEmployee($this, ['status' => Employee::PENDING]);

    $this->actingAs($actor)
        ->postJson("/api/v1/employees/{$employee->id}/approve")
        ->assertForbidden();
});

function employeeWorkflowPermission(Company $company, string $code): Permission
{
    [$module, $action] = explode('.', $code, 2);

    return Permission::create([
        'company_id' => $company->id,
        'code' => $code,
        'module' => $module,
        'action' => $action,
        'name' => $code,
    ]);
}

/**
 * @param  array<int, Permission|null>  $permissions
 */
function employeeWorkflowUser(Company $company, string $roleCode, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => $roleCode,
        'email' => $roleCode.'@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => $roleCode,
        'name' => $roleCode,
    ]);

    foreach (array_filter($permissions) as $permission) {
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function employeeWorkflowPayload(object $test, array $overrides = []): array
{
    return array_merge([
        'employee_number' => 'EMP-WF-001',
        'name' => 'Workflow Employee',
        'email' => 'workflow.employee@saneng.co.id',
        'phone' => '081200001111',
        'address' => 'Jl. Workflow No. 1',
        'birth_date' => '1990-01-01',
        'birth_place' => 'Karawang',
        'gender' => 'male',
        'department_id' => $test->department->id,
        'position_id' => $test->position->id,
        'employment_type_id' => $test->employmentType->id,
        'join_date' => '2024-01-01',
        'nik' => '3374010101010001',
        'npwp' => '09.123.456.7-891.000',
        'bank_name' => 'BCA',
        'bank_account_number' => '1234567890',
        'bank_account_holder_name' => 'Workflow Employee',
        'salary' => '10000000',
        'allowances' => '1500000',
        'deductions' => '250000',
        'consent_at' => now()->toDateTimeString(),
        'contract' => [
            'contract_type' => EmployeeContract::TYPE_PKWTT,
            'contract_number' => 'CTR-WF-001',
            'start_date' => '2024-01-01',
        ],
        'emergency_contact' => [
            'name' => 'Emergency Contact',
            'relationship' => 'Sibling',
            'phone' => '081299991111',
        ],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function employeeWorkflowEmployee(object $test, array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'company_id' => $test->company->id,
        'employee_number' => 'EMP-DIRECT-001',
        'name' => 'Direct Employee',
        'email' => 'direct.employee@saneng.co.id',
        'department_id' => $test->department->id,
        'position_id' => $test->position->id,
        'employment_type_id' => $test->employmentType->id,
        'nik' => '3374010101019999',
        'npwp' => '09.999.999.9-999.000',
        'bank_account_number' => '9999999999',
        'salary' => '9000000',
        'allowances' => '1000000',
        'deductions' => '100000',
        'consent_at' => now(),
        'consent_by' => employeeWorkflowUser($test->company, 'consent_'.strtolower(str_replace('-', '_', uniqid())), [])->id,
        'status' => Employee::DRAFT,
    ], $overrides));
}
