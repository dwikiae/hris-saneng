<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFamily;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeFamilyUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeFamilyEmployee($this->company, $this->user);
});

it('creates lists and updates employee family records', function () {
    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/family", [
        'name' => 'Siti Saneng',
        'relationship' => EmployeeFamily::RELATIONSHIP_SPOUSE,
        'birth_date' => '1992-02-02',
        'gender' => 'female',
        'occupation' => 'Teacher',
        'phone' => '081200002222',
        'is_dependent' => true,
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.family.created')
        ->assertJsonPath('data.name', 'Siti Saneng')
        ->assertJsonPath('data.is_dependent', true);

    $familyId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/family")
        ->assertOk()
        ->assertJsonPath('message', 'employee.family.list')
        ->assertJsonPath('data.0.id', $familyId);

    $this->putJson("/api/v1/employees/{$this->employee->id}/family/{$familyId}", [
        'occupation' => 'Accountant',
        'is_dependent' => false,
    ])->assertOk()
        ->assertJsonPath('message', 'employee.family.updated')
        ->assertJsonPath('data.occupation', 'Accountant')
        ->assertJsonPath('data.is_dependent', false);
});

it('archives employee family records without hard delete', function () {
    $family = EmployeeFamily::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'name' => 'Family Archive Target',
        'relationship' => EmployeeFamily::RELATIONSHIP_CHILD,
        'gender' => 'male',
        'is_dependent' => true,
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/family/{$family->id}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'employee.family.archived');

    expect(EmployeeFamily::withArchived()->findOrFail($family->id)->archived_at)->not->toBeNull();
    expect(EmployeeFamily::whereKey($family->id)->exists())->toBeFalse();
});

function employeeFamilyUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Family Admin',
        'email' => 'employee.family.admin@saneng.co.id',
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

function employeeFamilyEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-FAMILY-001',
        'name' => 'Family Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}
