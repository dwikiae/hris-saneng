<?php

use App\Models\Company;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeEducationUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeEducationEmployee($this->company, $this->user);
    $this->educationLevel = EducationLevel::create([
        'company_id' => $this->company->id,
        'code' => 'S1',
        'name' => 'Sarjana',
        'is_active' => true,
    ]);
});

it('creates lists and archives employee education records', function () {
    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/education", [
        'institution_name' => 'Universitas Saneng',
        'education_level_id' => $this->educationLevel->id,
        'major' => 'Information Systems',
        'start_year' => 2014,
        'end_year' => 2018,
        'gpa' => 3.75,
        'certificate_number' => 'CERT-001',
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.education.created')
        ->assertJsonPath('data.institution_name', 'Universitas Saneng')
        ->assertJsonPath('data.education_level_id', $this->educationLevel->id);

    $educationId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/education")
        ->assertOk()
        ->assertJsonPath('message', 'employee.education.list')
        ->assertJsonPath('data.0.id', $educationId);

    $this->postJson("/api/v1/employees/{$this->employee->id}/education/{$educationId}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'employee.education.archived');

    expect(EmployeeEducation::withArchived()->findOrFail($educationId)->archived_at)->not->toBeNull();
    expect(EmployeeEducation::whereKey($educationId)->exists())->toBeFalse();
});

it('rejects invalid education year range', function () {
    $this->postJson("/api/v1/employees/{$this->employee->id}/education", [
        'institution_name' => 'Universitas Saneng',
        'education_level_id' => $this->educationLevel->id,
        'start_year' => 2020,
        'end_year' => 2019,
    ])->assertUnprocessable();

    expect(EmployeeEducation::count())->toBe(0);
});

function employeeEducationUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Education Admin',
        'email' => 'employee.education.admin@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => 'system_admin',
        'name' => 'System Admin',
    ]);
    grantTestPermissions($role, $company, ['employee.view', 'employee.update', 'employee.archive']);

    $user->roles()->attach($role->id);

    return $user;
}

function employeeEducationEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-EDU-001',
        'name' => 'Education Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}
