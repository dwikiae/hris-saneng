<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeContractUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeContractEmployee($this->company, $this->user);
});

it('creates lists shows and updates employee contracts', function () {
    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/contracts", [
        'contract_type' => EmployeeContract::TYPE_PKWTT,
        'contract_number' => 'CTR-001',
        'start_date' => '2026-06-01',
        'notes' => 'Initial PKWTT contract.',
        'status' => EmployeeContract::STATUS_ACTIVE,
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.contracts.created')
        ->assertJsonPath('data.status', EmployeeContract::STATUS_DRAFT)
        ->assertJsonPath('data.contract_number', 'CTR-001');

    $contractId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/contracts")
        ->assertOk()
        ->assertJsonPath('message', 'employee.contracts.list')
        ->assertJsonPath('data.0.id', $contractId);

    $this->getJson("/api/v1/employees/{$this->employee->id}/contracts/{$contractId}")
        ->assertOk()
        ->assertJsonPath('message', 'employee.contracts.detail')
        ->assertJsonPath('data.id', $contractId);

    $this->putJson("/api/v1/employees/{$this->employee->id}/contracts/{$contractId}", [
        'contract_number' => 'CTR-001-REV',
        'notes' => null,
    ])->assertOk()
        ->assertJsonPath('message', 'employee.contracts.updated')
        ->assertJsonPath('data.contract_number', 'CTR-001-REV')
        ->assertJsonPath('data.notes', null);
});

it('requires end date for pkwt contracts', function () {
    $this->postJson("/api/v1/employees/{$this->employee->id}/contracts", [
        'contract_type' => EmployeeContract::TYPE_PKWT,
        'contract_number' => 'PKWT-001',
        'start_date' => '2026-06-01',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'employee.contracts.pkwt_end_date_required');

    expect(EmployeeContract::count())->toBe(0);
});

it('approves a contract and supersedes then archives previous active contract', function () {
    $first = EmployeeContract::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'contract_type' => EmployeeContract::TYPE_PKWTT,
        'contract_number' => 'CTR-ACTIVE',
        'start_date' => '2026-01-01',
        'status' => EmployeeContract::STATUS_ACTIVE,
    ]);

    $second = EmployeeContract::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'contract_type' => EmployeeContract::TYPE_PKWT,
        'contract_number' => 'CTR-NEW',
        'start_date' => '2026-06-01',
        'end_date' => '2027-05-31',
        'status' => EmployeeContract::STATUS_DRAFT,
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/contracts/{$second->id}/approve")
        ->assertOk()
        ->assertJsonPath('message', 'employee.contracts.approved')
        ->assertJsonPath('data.status', EmployeeContract::STATUS_ACTIVE)
        ->assertJsonPath('data.approved_by', $this->user->id);

    $archivedFirst = EmployeeContract::withArchived()->findOrFail($first->id);

    expect($archivedFirst->status)->toBe(EmployeeContract::STATUS_SUPERSEDED);
    expect($archivedFirst->archived_at)->not->toBeNull();
    expect(EmployeeContract::where('employee_id', $this->employee->id)
        ->where('status', EmployeeContract::STATUS_ACTIVE)
        ->count())->toBe(1);
});

function employeeContractUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Contract Admin',
        'email' => 'employee.contract.admin@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => 'system_admin',
        'name' => 'System Admin',
    ]);
    grantTestPermissions($role, $company, ['employee.view', 'employee.update', 'employee.archive', 'employee.approve']);

    $user->roles()->attach($role->id);

    return $user;
}

function employeeContractEmployee(Company $company, User $user): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-CONTRACT-001',
        'name' => 'Contract Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);
}
