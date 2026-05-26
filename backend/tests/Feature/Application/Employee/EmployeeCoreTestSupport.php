<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;

/**
 * @param  list<string>  $permissions
 * @return array<string, mixed>
 */
function employeeCoreFixture(array $permissions): array
{
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $actor = employeeCoreUser($company, 'hr_core', $permissions);
    $department = Department::create(['company_id' => $company->id, 'code' => 'HRD', 'name' => 'HRD']);
    $position = Position::create(['company_id' => $company->id, 'code' => 'STAFF', 'name' => 'Staff']);
    $employmentType = EmploymentType::create(['company_id' => $company->id, 'code' => 'PKWT', 'name' => 'PKWT']);

    return compact('company', 'actor', 'department', 'position', 'employmentType');
}

/**
 * @param  list<string>  $permissions
 */
function employeeCoreUser(Company $company, string $roleCode, array $permissions): User
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
            [
                'company_id' => $company->id,
                'module' => $module,
                'action' => $action,
            ],
            [
                'code' => $code,
                'name' => $code,
            ]
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
function employeeCorePayload(array $fixture, array $overrides = []): array
{
    return array_merge([
        'full_name' => 'A4 Employee',
        'gender' => 'male',
        'place_of_birth' => 'Karawang',
        'date_of_birth' => '1990-01-01',
        'department_id' => $fixture['department']->id,
        'position_id' => $fixture['position']->id,
        'employment_type_id' => $fixture['employmentType']->id,
        'join_date' => '2026-01-01',
        'address_ktp' => 'Jl. A4 No. 1',
        'nik' => '3374010101010001',
        'consent_at' => now()->toDateTimeString(),
        'consent_text' => 'Saya menyetujui pemrosesan data pribadi.',
        'approver_id' => $fixture['actor']->id,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $overrides
 */
function employeeCoreEmployee(array $fixture, array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'company_id' => $fixture['company']->id,
        'employee_number' => null,
        'full_name' => 'Pending A4',
        'name' => 'Pending A4',
        'department_id' => $fixture['department']->id,
        'position_id' => $fixture['position']->id,
        'employment_type_id' => $fixture['employmentType']->id,
        'nik' => '3374010101010002',
        'consent_at' => now(),
        'consent_by' => $fixture['actor']->id,
        'consent_text' => 'Consent text',
        'status' => Employee::PENDING,
    ], $overrides));
}
