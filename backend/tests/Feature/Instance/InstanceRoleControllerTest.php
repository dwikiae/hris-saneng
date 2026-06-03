<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires platform administrator for instance role endpoints', function () {
    $company = instanceRoleCompany('PT Role Scope');
    $user = instanceRoleUser($company);

    $this->getJson('/api/v1/instance/roles')->assertUnauthorized();

    $this->actingAs($user)
        ->getJson('/api/v1/instance/roles')
        ->assertForbidden();
});

it('creates lists shows updates archives and replaces role permissions', function () {
    $admin = instanceRoleAdmin();
    $company = instanceRoleCompany('PT Role');
    $permissionA = instancePermission($company, 'employee.view');
    $permissionB = instancePermission($company, 'employee.create');

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/instance/roles', [
            'name' => 'HR Operator',
            'description' => 'Operasional HR',
            'companyId' => $company->id,
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'instance.role.created')
        ->assertJsonPath('data.name', 'HR Operator')
        ->assertJsonPath('data.company.id', $company->id);

    $roleId = $response->json('data.id');

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/roles')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1);

    $this->actingAs($admin)
        ->patchJson("/api/v1/instance/roles/{$roleId}/permissions", [
            'permission_ids' => [$permissionA->id, $permissionB->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.permissionIds', [$permissionA->id, $permissionB->id]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/instance/roles/{$roleId}/permissions", [
            'permission_ids' => [$permissionB->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.permissionIds', [$permissionB->id]);

    $this->actingAs($admin)
        ->getJson("/api/v1/instance/roles/{$roleId}")
        ->assertOk()
        ->assertJsonPath('data.permissionCount', 1);

    $this->actingAs($admin)
        ->putJson("/api/v1/instance/roles/{$roleId}", [
            'name' => 'HR Operator Edit',
            'description' => 'Edit',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'HR Operator Edit');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/instance/roles/{$roleId}")
        ->assertOk()
        ->assertJsonPath('message', 'instance.role.archived');

    expect(Role::withArchived()->find($roleId))->not->toBeNull()
        ->and(Role::withArchived()->find($roleId)?->archived_by)->toBe($admin->id);

    $this->assertDatabaseHas('activity_log', ['description' => 'instance.role.created']);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.role.updated']);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.role.archived']);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.role.permissions_synced']);
});

it('returns a three level permission structure', function () {
    $admin = instanceRoleAdmin();
    $company = instanceRoleCompany('PT Permission');
    instancePermission($company, 'employee.view');

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/permissions/structure')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'label',
                    'menus' => [
                        '*' => [
                            'id',
                            'label',
                            'actions' => [
                                '*' => ['id', 'code', 'label', 'action'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
});

function instanceRoleAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Platform Administrator',
        'email' => uniqid('platform.role.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceRoleCompany(string $name): Company
{
    return Company::create([
        'name' => $name,
        'legal_name' => $name.' Legal',
        'slug' => str($name)->slug()->toString(),
    ]);
}

function instanceRoleUser(Company $company): User
{
    return User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('role.user.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instancePermission(Company $company, string $code): Permission
{
    [$module, $action] = explode('.', $code, 2);

    return Permission::create([
        'company_id' => $company->id,
        'code' => $code,
        'module' => $module,
        'action' => $action,
        'name' => $code,
    ]);
}
