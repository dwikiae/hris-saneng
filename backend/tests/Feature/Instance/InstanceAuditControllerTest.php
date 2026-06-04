<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('requires authentication for instance audit endpoints', function () {
    $this->getJson('/api/v1/instance/audit')
        ->assertUnauthorized();
});

it('blocks company users from instance audit endpoints', function () {
    $user = instanceAuditCompanyUser();

    $this->actingAs($user)
        ->getJson('/api/v1/instance/audit')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/api/v1/instance/audit/export?format=csv')
        ->assertForbidden();
});

it('allows platform admin to list audit logs with frontend contract shape', function () {
    $admin = instanceAuditAdmin();
    $activity = instanceAuditActivity($admin, [
        'log_name' => 'core',
        'event' => 'updated',
        'description' => 'companies.updated',
        'created_at' => now()->addMinutes(5),
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/audit?log_name=core&event=updated')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'instance.audit.list')
        ->assertJsonPath('data.items.0.id', $activity->id)
        ->assertJsonPath('data.items.0.actor', $admin->name.' <'.$admin->email.'>')
        ->assertJsonPath('data.items.0.action', 'updated')
        ->assertJsonPath('data.items.0.module', 'core')
        ->assertJsonPath('data.items.0.detail', 'companies.updated')
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    ['id', 'createdAt', 'actor', 'action', 'module', 'entity', 'detail', 'diffs'],
                ],
                'meta' => ['total', 'today', 'this_week', 'current_page', 'per_page', 'last_page'],
            ],
            'meta',
        ]);
});

it('filters audit logs by required and frontend alias filters', function () {
    $admin = instanceAuditAdmin();
    $included = instanceAuditActivity($admin, [
        'log_name' => 'recruitment',
        'event' => 'created',
        'description' => 'applicants.created',
        'created_at' => '2026-06-02 10:00:00',
    ]);

    instanceAuditActivity($admin, [
        'log_name' => 'recruitment',
        'event' => 'updated',
        'description' => 'applicants.updated',
        'created_at' => '2026-06-02 11:00:00',
    ]);
    instanceAuditActivity($admin, [
        'log_name' => 'core',
        'event' => 'created',
        'description' => 'companies.created',
        'created_at' => '2026-06-03 10:00:00',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/audit?date_from=2026-06-01&date_to=2026-06-02&causer_id='.$admin->id.'&module=recruitment&action=created&actor='.urlencode($admin->email))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $included->id);
});

it('uses default pagination of twenty rows', function () {
    $admin = instanceAuditAdmin();

    foreach (range(1, 25) as $index) {
        instanceAuditActivity($admin, [
            'log_name' => 'pagination',
            'event' => 'updated',
            'description' => 'pagination.updated',
            'created_at' => now()->addMinutes($index),
        ]);
    }

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/audit?log_name=pagination')
        ->assertOk()
        ->assertJsonCount(20, 'data.items')
        ->assertJsonPath('data.meta.per_page', 20)
        ->assertJsonPath('data.meta.total', 25);
});

it('redacts sensitive audit properties without audit view sensitive permission', function () {
    $admin = instanceAuditAdmin();
    instanceAuditActivity($admin, [
        'properties' => [
            'attributes' => [
                'name' => 'Updated User',
                'nik' => '1234567890',
                'salary' => 10000000,
            ],
            'old' => [
                'name' => 'Old User',
                'nik' => '0987654321',
                'salary' => 9000000,
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/audit?log_name=employees')
        ->assertOk()
        ->assertJsonPath('data.items.0.diffs.0.field', 'name')
        ->assertJsonPath('data.items.0.diffs.0.oldValue', 'Old User')
        ->assertJsonPath('data.items.0.diffs.1.field', 'nik')
        ->assertJsonPath('data.items.0.diffs.1.oldValue', '[REDACTED]')
        ->assertJsonPath('data.items.0.diffs.2.field', 'salary')
        ->assertJsonPath('data.items.0.diffs.2.newValue', '[REDACTED]');
});

it('reveals sensitive audit properties with audit view sensitive permission', function () {
    $admin = instanceAuditAdmin();
    grantInstanceAuditSensitivePermission($admin);
    instanceAuditActivity($admin, [
        'properties' => [
            'attributes' => ['salary' => 10000000],
            'old' => ['salary' => 9000000],
        ],
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/audit?log_name=employees')
        ->assertOk()
        ->assertJsonPath('data.items.0.diffs.0.field', 'salary')
        ->assertJsonPath('data.items.0.diffs.0.oldValue', 9000000)
        ->assertJsonPath('data.items.0.diffs.0.newValue', 10000000);
});

it('exports audit logs as csv and records the export activity', function () {
    $admin = instanceAuditAdmin();
    instanceAuditActivity($admin, [
        'log_name' => 'core',
        'event' => 'created',
        'description' => 'companies.created',
    ]);

    $response = $this->actingAs($admin)
        ->get('/api/v1/instance/audit/export?format=csv&log_name=core');

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="audit-log.csv"');

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->getContent())->toContain('companies.created');

    $this->assertDatabaseHas('activity_log', ['description' => 'instance.audit.exported']);
});

it('exports audit logs as a valid xlsx file', function () {
    $admin = instanceAuditAdmin();
    instanceAuditActivity($admin, [
        'log_name' => 'core',
        'event' => 'updated',
        'description' => 'companies.updated',
    ]);

    $response = $this->actingAs($admin)
        ->get('/api/v1/instance/audit/export?format=xlsx&log_name=core');

    $path = tempnam(sys_get_temp_dir(), 'audit-test-xlsx-');
    file_put_contents((string) $path, $response->getContent());

    $zip = new ZipArchive;
    $opened = $zip->open((string) $path);
    $hasSheet = $zip->locateName('xl/worksheets/sheet1.xml') !== false;
    $zip->close();
    @unlink((string) $path);

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="audit-log.xlsx"');

    expect($response->headers->get('content-type'))->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($opened)->toBeTrue()
        ->and($hasSheet)->toBeTrue();
});

it('supports post export compatibility for the existing frontend export button', function () {
    $admin = instanceAuditAdmin();
    instanceAuditActivity($admin, [
        'log_name' => 'core',
        'event' => 'exported',
        'description' => 'companies.exported',
    ]);

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/instance/audit/export', [
            'format' => 'csv',
            'filename' => 'audit-log',
            'filters' => ['module' => 'core', 'action' => 'exported'],
        ]);

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->getContent())->toContain('companies.exported');
});

function instanceAuditAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Platform Audit Admin',
        'email' => uniqid('platform.audit.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceAuditCompanyUser(): User
{
    $company = Company::create(['name' => uniqid('PT Audit ', false), 'legal_name' => 'PT Audit Legal']);

    return User::create([
        'company_id' => $company->id,
        'name' => 'Company Audit User',
        'email' => uniqid('company.audit.', true).'@example.test',
        'password' => 'password',
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function instanceAuditActivity(User $causer, array $overrides = []): Activity
{
    $createdAt = $overrides['created_at'] ?? now()->addMinute();

    return Activity::create([
        'log_name' => $overrides['log_name'] ?? 'employees',
        'description' => $overrides['description'] ?? 'employees.updated',
        'subject_type' => User::class,
        'subject_id' => $causer->id,
        'causer_type' => User::class,
        'causer_id' => $causer->id,
        'event' => $overrides['event'] ?? 'updated',
        'properties' => $overrides['properties'] ?? [
            'attributes' => ['name' => 'Updated User'],
            'old' => ['name' => 'Old User'],
        ],
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

function grantInstanceAuditSensitivePermission(User $user): void
{
    $company = Company::create(['name' => uniqid('PT Sensitive ', false), 'legal_name' => 'PT Sensitive Legal']);
    $role = Role::create([
        'company_id' => $company->id,
        'code' => 'sensitive-auditor',
        'name' => 'Sensitive Auditor',
    ]);
    $permission = Permission::create([
        'company_id' => $company->id,
        'code' => 'audit.view_sensitive',
        'module' => 'audit',
        'action' => 'view_sensitive',
        'name' => 'View Sensitive Audit Log',
        'description' => 'View sensitive audit log fields',
    ]);

    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);
}
