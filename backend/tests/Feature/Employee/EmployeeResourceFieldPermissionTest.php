<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);

    $this->viewPermission = Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view',
        'module' => 'employee',
        'action' => 'view',
        'name' => 'View Employee',
    ]);

    $this->salaryPermission = Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view_salary',
        'module' => 'employee',
        'action' => 'view_salary',
        'name' => 'View Employee Salary',
    ]);
    $this->sensitivePermission = Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view_sensitive',
        'module' => 'employee',
        'action' => 'view_sensitive',
        'name' => 'View Employee Sensitive Data',
    ]);

    $this->consentUser = User::create([
        'company_id' => $this->company->id,
        'name' => 'Consent User',
        'email' => 'consent@saneng.co.id',
        'password' => 'password',
    ]);

    $this->employee = Employee::create([
        'company_id' => $this->company->id,
        'employee_number' => 'EMP-001',
        'name' => 'Budi Saneng',
        'email' => 'budi@saneng.co.id',
        'nik' => '3374010101010001',
        'npwp' => '09.123.456.7-891.000',
        'passport_number' => 'A1234567',
        'bank_name' => 'BCA',
        'bank_account_number' => '1234567890',
        'bank_account_holder_name' => 'Budi Saneng',
        'salary' => '10000000',
        'allowances' => '1500000',
        'deductions' => '250000',
        'consent_at' => now(),
        'consent_by' => $this->consentUser->id,
        'status' => Employee::DRAFT,
    ]);

    $this->otherEmployee = Employee::create([
        'company_id' => $this->company->id,
        'employee_number' => 'EMP-002',
        'name' => 'Siti Saneng',
        'nik' => '3374010101010002',
        'npwp' => '09.123.456.7-891.001',
        'passport_number' => 'B7654321',
        'bank_account_number' => '0987654321',
        'bank_account_holder_name' => 'Siti Saneng',
        'salary' => '12000000',
        'allowances' => '2000000',
        'deductions' => '300000',
        'consent_at' => now(),
        'consent_by' => $this->consentUser->id,
        'status' => Employee::DRAFT,
    ]);
});

it('excludes sensitive and salary fields without their permissions', function () {
    $user = userWithRole($this->company, 'hr_staff', [$this->viewPermission]);

    $this->actingAs($user)
        ->getJson("/api/v1/employees/{$this->employee->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.nik')
        ->assertJsonMissingPath('data.passport_number')
        ->assertJsonMissingPath('data.bank_account_number')
        ->assertJsonMissingPath('data.bank_account_holder_name')
        ->assertJsonMissingPath('data.salary')
        ->assertJsonMissingPath('data.allowances')
        ->assertJsonMissingPath('data.deductions');
});

it('includes sensitive and salary fields for users with explicit permissions', function () {
    $user = userWithRole($this->company, 'payroll', [$this->viewPermission, $this->sensitivePermission, $this->salaryPermission]);

    $this->actingAs($user)
        ->getJson("/api/v1/employees/{$this->employee->id}")
        ->assertOk()
        ->assertJsonPath('data.nik', '3374010101010001')
        ->assertJsonPath('data.passport_number', 'A1234567')
        ->assertJsonPath('data.bank_account_number', '1234567890')
        ->assertJsonPath('data.bank_account_holder_name', 'Budi Saneng')
        ->assertJsonPath('data.salary', '10000000')
        ->assertJsonPath('data.allowances', '1500000')
        ->assertJsonPath('data.deductions', '250000');
});

it('excludes identity fields when employee user views another employee', function () {
    $user = userWithRole($this->company, 'employee_self_service', [$this->viewPermission], $this->otherEmployee->id);

    $this->actingAs($user)
        ->getJson("/api/v1/employees/{$this->employee->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.nik')
        ->assertJsonMissingPath('data.npwp')
        ->assertJsonMissingPath('data.passport_number')
        ->assertJsonMissingPath('data.bank_account_number')
        ->assertJsonMissingPath('data.salary');
});

it('does not include identity fields for own employee record without sensitive permission', function () {
    $user = userWithRole($this->company, 'employee_self_service_own', [$this->viewPermission], $this->employee->id);

    $this->actingAs($user)
        ->getJson("/api/v1/employees/{$this->employee->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.nik')
        ->assertJsonMissingPath('data.npwp')
        ->assertJsonMissingPath('data.passport_number')
        ->assertJsonMissingPath('data.bank_account_number')
        ->assertJsonMissingPath('data.salary');
});

it('includes all sensitive fields for system admin through synced permissions', function () {
    $admin = userWithRole($this->company, 'system_admin', [$this->viewPermission, $this->sensitivePermission, $this->salaryPermission], $this->otherEmployee->id);

    $this->actingAs($admin)
        ->getJson("/api/v1/employees/{$this->employee->id}")
        ->assertOk()
        ->assertJsonPath('data.nik', '3374010101010001')
        ->assertJsonPath('data.npwp', '09.123.456.7-891.000')
        ->assertJsonPath('data.passport_number', 'A1234567')
        ->assertJsonPath('data.bank_account_number', '1234567890')
        ->assertJsonPath('data.bank_account_holder_name', 'Budi Saneng')
        ->assertJsonPath('data.salary', '10000000')
        ->assertJsonPath('data.allowances', '1500000')
        ->assertJsonPath('data.deductions', '250000');
});

it('applies field permissions to employee index items', function () {
    $user = userWithRole($this->company, 'employee_index_user', [$this->viewPermission], $this->otherEmployee->id);

    $this->actingAs($user)
        ->getJson('/api/v1/employees')
        ->assertOk()
        ->assertJsonPath('data.data.0.name', 'Budi Saneng')
        ->assertJsonMissingPath('data.data.0.nik')
        ->assertJsonMissingPath('data.data.0.passport_number')
        ->assertJsonMissingPath('data.data.0.salary');
});

function userWithRole(Company $company, string $roleCode, array $permissions, ?int $employeeId = null): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => $roleCode,
        'email' => $roleCode.'@saneng.co.id',
        'password' => 'password',
        'employee_id' => $employeeId,
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => $roleCode,
        'name' => $roleCode,
    ]);

    foreach ($permissions as $permission) {
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}
