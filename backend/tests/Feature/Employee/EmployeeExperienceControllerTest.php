<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeExperience;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeExperienceUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeExperienceEmployee($this->company, $this->user);
});

it('creates and lists employee experience records', function () {
    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/experience", [
        'company_name' => 'PT Lama',
        'position' => 'HR Staff',
        'start_date' => '2020-01-01',
        'end_date' => '2022-12-31',
        'responsibilities' => 'Handled payroll administration.',
        'reason_leaving' => 'Career growth',
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.experience.created')
        ->assertJsonPath('data.company_name', 'PT Lama')
        ->assertJsonPath('data.is_current', false);

    $experienceId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/experience")
        ->assertOk()
        ->assertJsonPath('message', 'employee.experience.list')
        ->assertJsonPath('data.0.id', $experienceId);
});

it('rejects current experience with end date', function () {
    $this->postJson("/api/v1/employees/{$this->employee->id}/experience", [
        'company_name' => 'PT Sekarang',
        'position' => 'HR Supervisor',
        'start_date' => '2023-01-01',
        'end_date' => '2024-01-01',
        'is_current' => true,
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'employee.experience.current_end_date_conflict');

    expect(EmployeeExperience::count())->toBe(0);
});

it('archives employee experience records without hard delete', function () {
    $experience = EmployeeExperience::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'company_name' => 'PT Archive',
        'position' => 'Recruiter',
        'start_date' => '2018-01-01',
        'end_date' => '2019-01-01',
        'is_current' => false,
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/experience/{$experience->id}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'employee.experience.archived');

    expect(EmployeeExperience::withArchived()->findOrFail($experience->id)->archived_at)->not->toBeNull();
    expect(EmployeeExperience::whereKey($experience->id)->exists())->toBeFalse();
});

function employeeExperienceUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Experience Admin',
        'email' => 'employee.experience.admin@saneng.co.id',
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

function employeeExperienceEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-EXP-001',
        'name' => 'Experience Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}
