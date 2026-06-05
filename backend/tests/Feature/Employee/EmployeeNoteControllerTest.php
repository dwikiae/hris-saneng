<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeNote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = employeeNoteUser($this->company);
    $this->actingAs($this->user);
    $this->employee = employeeNoteEmployee($this->company, $this->user);
});

it('creates manual notes and lists them as append-only chatter', function () {
    $mentionedUser = User::create([
        'company_id' => $this->company->id,
        'name' => 'Mention Target',
        'email' => 'mention.target@saneng.co.id',
        'password' => 'password',
    ]);

    $createResponse = $this->postJson("/api/v1/employees/{$this->employee->id}/notes", [
        'content' => 'Manual HR note.',
        'type' => EmployeeNote::TYPE_SYSTEM,
        'mentioned_users' => [$mentionedUser->id],
    ])->assertCreated()
        ->assertJsonPath('message', 'employee.notes.created')
        ->assertJsonPath('data.content', 'Manual HR note.')
        ->assertJsonPath('data.type', EmployeeNote::TYPE_MANUAL)
        ->assertJsonPath('data.mentioned_users.0', $mentionedUser->id);

    $noteId = $createResponse->json('data.id');

    $this->getJson("/api/v1/employees/{$this->employee->id}/notes")
        ->assertOk()
        ->assertJsonPath('message', 'employee.notes.list')
        ->assertJsonPath('data.0.id', $noteId);
});

it('creates system notes when employee is approved and rejected', function () {
    $approvedEmployee = employeeNoteEmployee($this->company, $this->user, [
        'employee_number' => 'EMP-NOTE-APPROVE',
        'status' => Employee::PENDING,
    ]);
    $rejectedEmployee = employeeNoteEmployee($this->company, $this->user, [
        'employee_number' => 'EMP-NOTE-REJECT',
        'status' => Employee::PENDING,
    ]);

    $this->postJson("/api/v1/employees/{$approvedEmployee->id}/approve")
        ->assertOk()
        ->assertJsonPath('message', 'employee.approved');

    $this->postJson("/api/v1/employees/{$rejectedEmployee->id}/reject", [
        'reason' => 'Data belum lengkap',
    ])->assertOk()
        ->assertJsonPath('message', 'employee.rejected');

    expect(EmployeeNote::where('employee_id', $approvedEmployee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Disetujui oleh '.$this->user->name)
        ->exists())->toBeTrue();
    expect(EmployeeNote::where('employee_id', $rejectedEmployee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Ditolak: Data belum lengkap')
        ->exists())->toBeTrue();
});

it('creates system notes when contracts are approved and superseded', function () {
    $activeContract = EmployeeContract::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'contract_type' => EmployeeContract::TYPE_PKWTT,
        'contract_number' => 'CTR-ACTIVE',
        'start_date' => '2026-01-01',
        'status' => EmployeeContract::STATUS_ACTIVE,
    ]);
    $newContract = EmployeeContract::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'contract_type' => EmployeeContract::TYPE_PKWTT,
        'contract_number' => 'CTR-NEW',
        'start_date' => '2026-06-01',
        'status' => EmployeeContract::STATUS_DRAFT,
    ]);

    $this->postJson("/api/v1/employees/{$this->employee->id}/contracts/{$newContract->id}/approve")
        ->assertOk()
        ->assertJsonPath('message', 'employee.contracts.approved');

    expect(EmployeeNote::where('employee_id', $this->employee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Kontrak CTR-ACTIVE digantikan')
        ->exists())->toBeTrue();
    expect(EmployeeNote::where('employee_id', $this->employee->id)
        ->where('type', EmployeeNote::TYPE_SYSTEM)
        ->where('content', 'Kontrak CTR-NEW diaktifkan')
        ->exists())->toBeTrue();
    expect(EmployeeContract::withArchived()->findOrFail($activeContract->id)->status)
        ->toBe(EmployeeContract::STATUS_SUPERSEDED);
});

function employeeNoteUser(Company $company): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Note Admin',
        'email' => 'employee.note.admin@saneng.co.id',
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

function employeeNoteEmployee(Company $company, User $user, array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'company_id' => $company->id,
        'employee_number' => 'EMP-NOTE-001',
        'name' => 'Note Employee',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ], $overrides));
}
