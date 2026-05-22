<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
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

    $this->putJson("/api/v1/employees/{$employeeId}", [
        'salary' => '11000000',
    ])->assertOk()
        ->assertJsonPath('message', 'employee.updated')
        ->assertJsonPath('data.status', Employee::PENDING);

    $this->postJson("/api/v1/employees/{$employeeId}/approve")
        ->assertOk()
        ->assertJsonPath('message', 'employee.approved')
        ->assertJsonPath('data.status', Employee::APPROVED)
        ->assertJsonPath('data.approved_by', $actor->id);

    $rejectResponse = $this->postJson('/api/v1/employees', employeeWorkflowPayload($this, [
        'employee_number' => 'EMP-WF-002',
        'email' => 'workflow.reject@saneng.co.id',
        'nik' => '3374010101010002',
    ]))->assertCreated();

    $rejectEmployeeId = $rejectResponse->json('data.id');

    $this->putJson("/api/v1/employees/{$rejectEmployeeId}", [
        'department_id' => $this->otherDepartment->id,
    ])->assertOk()
        ->assertJsonPath('data.status', Employee::PENDING);

    $this->postJson("/api/v1/employees/{$rejectEmployeeId}/reject", [
        'reason' => 'Need supporting document.',
    ])->assertOk()
        ->assertJsonPath('message', 'employee.rejected')
        ->assertJsonPath('data.status', Employee::REJECTED)
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

it('requires consent when creating employee', function () {
    $actor = employeeWorkflowUser($this->company, 'hr_manager_consent', $this->permissions->values()->all());
    $payload = employeeWorkflowPayload($this);
    unset($payload['consent_at']);

    $this->actingAs($actor)
        ->postJson('/api/v1/employees', $payload)
        ->assertUnprocessable();

    expect(Employee::where('employee_number', $payload['employee_number'])->exists())->toBeFalse();
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
        'salary' => '10000000',
        'allowances' => '1500000',
        'deductions' => '250000',
        'consent_at' => now()->toDateTimeString(),
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
