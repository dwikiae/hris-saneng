<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id' => $this->company->id,
        'name' => 'Auditor',
        'email' => 'auditor@saneng.co.id',
        'password' => 'password',
    ]);
});

function grantAuditPermission(User $user, bool $withSensitive = false): void
{
    $role = Role::create([
        'company_id' => $user->company_id,
        'code' => 'auditor',
        'name' => 'Auditor',
    ]);

    $audit = Permission::create([
        'company_id' => $user->company_id,
        'code' => 'audit.view',
        'module' => 'audit',
        'action' => 'view',
        'name' => 'View Audit Log',
    ]);

    $role->permissions()->attach($audit->id);

    if ($withSensitive) {
        $salary = Permission::create([
            'company_id' => $user->company_id,
            'code' => 'employee.view_salary',
            'module' => 'employee',
            'action' => 'view_salary',
            'name' => 'View Salary',
        ]);

        $role->permissions()->attach($salary->id);
    }

    $user->roles()->attach($role->id);
}

function createAuditActivity(User $causer): Activity
{
    return Activity::create([
        'log_name' => 'employees',
        'description' => 'employees.updated',
        'subject_type' => User::class,
        'subject_id' => $causer->id,
        'causer_type' => User::class,
        'causer_id' => $causer->id,
        'event' => 'updated',
        'properties' => [
            'attributes' => [
                'name' => 'Updated User',
                'salary' => 10000000,
                'nik' => '1234567890',
                'bank_account_number' => '123456789',
                'remember_token' => 'plain-token',
            ],
            'old' => [
                'name' => 'Old User',
                'salary' => 9000000,
                'nik' => '0987654321',
                'bank_account_number' => '987654321',
                'remember_token' => 'old-token',
            ],
            'ip_address' => '10.0.0.1',
        ],
        'created_at' => now()->addMinute(),
        'updated_at' => now()->addMinute(),
    ]);
}

function createAuditActivityFor(User $subject, User $causer, string $createdAt): Activity
{
    return Activity::create([
        'log_name' => 'employees',
        'description' => 'employees.updated',
        'subject_type' => User::class,
        'subject_id' => $subject->id,
        'causer_type' => User::class,
        'causer_id' => $causer->id,
        'event' => 'updated',
        'properties' => ['attributes' => ['name' => $subject->name]],
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

it('returns paginated audit logs with redacted sensitive fields', function () {
    grantAuditPermission($this->user);
    createAuditActivity($this->user);

    $this->actingAs($this->user)
        ->getJson('/api/v1/audit?subject_type='.urlencode(User::class).'&causer_id='.$this->user->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'audit.list')
        ->assertJsonPath('data.data.0.who.id', $this->user->id)
        ->assertJsonPath('data.data.0.subject.type', User::class)
        ->assertJsonPath('data.data.0.changes.attributes.name', 'Updated User')
        ->assertJsonPath('data.data.0.changes.attributes.salary', '[REDACTED]')
        ->assertJsonPath('data.data.0.changes.attributes.bank_account_number', '[REDACTED]')
        ->assertJsonPath('data.data.0.changes.attributes.remember_token', '[REDACTED]')
        ->assertJsonPath('data.data.0.changes.old.nik', '[REDACTED]')
        ->assertJsonPath('data.data.0.ip_address', '10.0.0.1');
});

it('only reveals permissioned compensation fields for users with salary permission', function () {
    grantAuditPermission($this->user, withSensitive: true);
    createAuditActivity($this->user);

    $this->actingAs($this->user)
        ->getJson('/api/v1/audit?subject_type='.urlencode(User::class))
        ->assertOk()
        ->assertJsonPath('data.data.0.changes.attributes.salary', 10000000)
        ->assertJsonPath('data.data.0.changes.old.nik', '[REDACTED]')
        ->assertJsonPath('data.data.0.changes.old.bank_account_number', '[REDACTED]')
        ->assertJsonPath('data.data.0.changes.old.remember_token', '[REDACTED]');
});

it('filters audit logs by subject type causer and date range', function () {
    grantAuditPermission($this->user);
    $otherUser = User::create([
        'company_id' => $this->company->id,
        'name' => 'Other User',
        'email' => 'other.audit.co.id',
        'password' => 'password',
    ]);

    $included = createAuditActivityFor($this->user, $this->user, '2026-05-15 10:00:00');
    createAuditActivityFor($this->user, $this->user, '2026-05-01 10:00:00');
    createAuditActivityFor($this->user, $this->user, '2026-05-30 10:00:00');
    createAuditActivityFor($otherUser, $otherUser, '2026-05-15 10:00:00');
    Activity::create([
        'log_name' => 'companies',
        'description' => 'companies.updated',
        'subject_type' => Company::class,
        'subject_id' => $this->company->id,
        'causer_type' => User::class,
        'causer_id' => $this->user->id,
        'event' => 'updated',
        'properties' => ['attributes' => ['name' => 'PT Saneng']],
        'created_at' => '2026-05-15 10:00:00',
        'updated_at' => '2026-05-15 10:00:00',
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/audit?subject_type='.urlencode(User::class).'&causer_id='.$this->user->id.'&date_from=2026-05-10&date_to=2026-05-20')
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $included->id)
        ->assertJsonPath('data.data.0.subject.type', User::class)
        ->assertJsonPath('data.data.0.who.id', $this->user->id);
});

it('requires audit view permission', function () {
    createAuditActivity($this->user);

    $this->actingAs($this->user)
        ->getJson('/api/v1/audit')
        ->assertForbidden();
});
