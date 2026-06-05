<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeOffboarding;
use App\Models\OffboardingChecklistItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = offboardingChecklistUser($this->company);
    $this->actingAs($this->user);
    $this->employee = offboardingChecklistEmployee($this->company, $this->user);
    $this->offboarding = offboardingChecklistRecord($this->company, $this->employee, $this->user);
});

it('creates lists updates completes and archives checklist items', function () {
    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding/{$this->offboarding->id}/checklist", [
        'title' => 'Return laptop',
        'description' => 'Return laptop and charger.',
        'assigned_to' => $this->user->id,
        'due_date' => '2026-07-25',
        'order' => 10,
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.offboarding.checklist.created')
        ->assertJsonPath('data.title', 'Return laptop')
        ->assertJsonPath('data.is_completed', false);

    $itemId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/offboarding/{$this->offboarding->id}/checklist")
        ->assertOk()
        ->assertJsonPath('message', 'employee.offboarding.checklist.list')
        ->assertJsonPath('data.0.id', $itemId);

    $this->putJson("/api/v1/employees/{$this->employee->id}/offboarding/{$this->offboarding->id}/checklist/{$itemId}", [
        'title' => 'Return laptop and badge',
        'order' => 20,
    ])->assertOk()
        ->assertJsonPath('message', 'employee.offboarding.checklist.updated')
        ->assertJsonPath('data.title', 'Return laptop and badge')
        ->assertJsonPath('data.order', 20);

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding/{$this->offboarding->id}/checklist/{$itemId}/complete")
        ->assertOk()
        ->assertJsonPath('message', 'employee.offboarding.checklist.completed')
        ->assertJsonPath('data.is_completed', true)
        ->assertJsonPath('data.completed_by', $this->user->id);

    $this->postJson("/api/v1/employees/{$this->employee->id}/offboarding/{$this->offboarding->id}/checklist/{$itemId}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'employee.offboarding.checklist.archived');

    expect(OffboardingChecklistItem::withArchived()->findOrFail($itemId)->archived_at)->not->toBeNull();
    expect(OffboardingChecklistItem::whereKey($itemId)->exists())->toBeFalse();
});

function offboardingChecklistUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Offboarding Checklist Admin',
        'email' => 'offboarding.checklist.admin@saneng.co.id',
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

function offboardingChecklistEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-CHECKLIST-001',
        'name' => 'Checklist Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}

function offboardingChecklistRecord(Company $company, Employee $employee, User $user): EmployeeOffboarding
{
    return EmployeeOffboarding::create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'reason_type' => EmployeeOffboarding::REASON_RESIGNATION,
        'last_working_date' => '2026-07-31',
        'status' => EmployeeOffboarding::STATUS_DRAFT,
        'initiated_by' => $user->id,
        'initiated_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}
