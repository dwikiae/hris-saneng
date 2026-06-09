<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeEmergencyContactUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeEmergencyContactEmployee($this->company, $this->user);
});

it('creates lists updates and archives emergency contacts without hard delete', function () {
    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/emergency-contacts", [
        'name' => 'Siti Kontak',
        'relationship' => 'Sibling',
        'phone' => '081200009999',
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.emergency_contacts.created')
        ->assertJsonPath('data.name', 'Siti Kontak');

    $contactId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/emergency-contacts")
        ->assertOk()
        ->assertJsonPath('message', 'employee.emergency_contacts.list')
        ->assertJsonPath('data.0.id', $contactId);

    $this->patchJson("/api/v1/employees/{$this->employee->id}/emergency-contacts/{$contactId}", [
        'relationship' => 'Parent',
    ])->assertOk()
        ->assertJsonPath('message', 'employee.emergency_contacts.updated')
        ->assertJsonPath('data.relationship', 'Parent');

    $this->patchJson("/api/v1/employees/{$this->employee->id}/emergency-contacts/{$contactId}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'employee.emergency_contacts.archived');

    expect(EmployeeEmergencyContact::withArchived()->findOrFail($contactId)->archived_at)->not->toBeNull();
    expect(EmployeeEmergencyContact::whereKey($contactId)->exists())->toBeFalse();
});

it('keeps delete archive route for backward compatibility', function () {
    $contact = EmployeeEmergencyContact::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'name' => 'Legacy Archive Contact',
        'relationship' => 'Sibling',
        'phone' => '081200008888',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $this->deleteJson("/api/v1/employees/{$this->employee->id}/emergency-contacts/{$contact->id}")
        ->assertOk()
        ->assertJsonPath('message', 'employee.emergency_contacts.archived');

    expect(EmployeeEmergencyContact::withArchived()->findOrFail($contact->id)->archived_at)->not->toBeNull();
    expect(EmployeeEmergencyContact::whereKey($contact->id)->exists())->toBeFalse();
});

function employeeEmergencyContactUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Emergency Contact Admin',
        'email' => 'employee.emergency.admin@saneng.co.id',
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

function employeeEmergencyContactEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-EMERGENCY-001',
        'name' => 'Emergency Contact Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}
