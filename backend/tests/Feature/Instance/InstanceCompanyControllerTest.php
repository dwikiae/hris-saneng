<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('requires authentication for instance company endpoints', function () {
    $this->getJson('/api/v1/instance/companies')
        ->assertUnauthorized();
});

it('blocks company users even when they have company permissions', function () {
    $company = instanceCompanyRecord('PT Scoped');
    $user = instanceCompanyUser($company, ['company.view', 'company.create', 'company.update', 'company.archive']);

    $this->actingAs($user)
        ->getJson('/api/v1/instance/companies')
        ->assertForbidden();
});

it('allows platform administrator to list companies with frontend contract shape', function () {
    $admin = instanceCompanyAdmin();
    instanceCompanyRecord('PT Alpha');
    instanceCompanyRecord('PT Beta');

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/companies')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'instance.company.list')
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => ['id', 'name', 'legalName', 'employeeCount', 'status', 'activeModules'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ],
            'meta' => ['current_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.meta.total', 2);
});

it('creates a company from camelCase frontend payload and stores extended settings', function () {
    $admin = instanceCompanyAdmin();

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/instance/companies', instanceCompanyPayload([
            'name' => 'PT Frontend Baru',
            'legalName' => 'PT Frontend Baru Legal',
            'companyType' => 'PT',
            'province' => 'DKI Jakarta',
            'activeModuleCodes' => ['recruitment', 'website'],
        ]))
        ->assertCreated()
        ->assertJsonPath('message', 'instance.company.created')
        ->assertJsonPath('data.name', 'PT Frontend Baru')
        ->assertJsonPath('data.legalName', 'PT Frontend Baru Legal')
        ->assertJsonPath('data.companyType', 'PT')
        ->assertJsonPath('data.province', 'DKI Jakarta')
        ->assertJsonPath('data.slug', 'pt-frontend-baru');

    $companyId = $response->json('data.id');

    $this->assertDatabaseHas('companies', [
        'id' => $companyId,
        'name' => 'PT Frontend Baru',
        'legal_name' => 'PT Frontend Baru Legal',
    ]);
    $this->assertDatabaseHas('company_settings', [
        'company_id' => $companyId,
        'key' => 'company_type',
        'value' => 'PT',
    ]);
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'companies',
        'description' => 'companies.created',
    ]);
    $this->assertDatabaseHas('roles', [
        'company_id' => $companyId,
        'code' => 'system_admin',
        'name' => 'Platform Administrator',
    ]);

    $role = Role::withoutCompanyScope()
        ->where('company_id', $companyId)
        ->where('code', 'system_admin')
        ->firstOrFail();

    $this->assertDatabaseHas('user_roles', [
        'user_id' => $admin->id,
        'role_id' => $role->id,
    ]);
});

it('shows detail with employee count, status, active modules, and extended fields', function () {
    $admin = instanceCompanyAdmin();
    $company = instanceCompanyRecord('PT Detail');
    instanceCompanyUser($company, []);
    Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-001',
        'name' => 'Employee One',
        'consent_at' => now(),
        'consent_by' => $admin->id,
    ]);
    $company->settings()->create(['key' => 'active_module_codes', 'value' => json_encode(['recruitment'])]);
    $company->settings()->create(['key' => 'company_type', 'value' => 'CV']);

    $this->actingAs($admin)
        ->getJson("/api/v1/instance/companies/{$company->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'PT Detail')
        ->assertJsonPath('data.companyType', 'CV')
        ->assertJsonPath('data.employeeCount', 1)
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.activeModules.0.code', 'recruitment');
});

it('updates core and extended company data without changing slug when slug is omitted', function () {
    $admin = instanceCompanyAdmin();
    $company = instanceCompanyRecord('PT Update');

    $this->actingAs($admin)
        ->putJson("/api/v1/instance/companies/{$company->id}", instanceCompanyPayload([
            'name' => 'PT Update Baru',
            'legalName' => 'PT Update Baru Legal',
            'city' => 'Bandung',
            'companyType' => 'Yayasan',
        ]))
        ->assertOk()
        ->assertJsonPath('data.name', 'PT Update Baru')
        ->assertJsonPath('data.city', 'Bandung')
        ->assertJsonPath('data.companyType', 'Yayasan')
        ->assertJsonPath('data.slug', 'pt-update');

    $this->assertDatabaseHas('company_settings', [
        'company_id' => $company->id,
        'key' => 'company_type',
        'value' => 'Yayasan',
    ]);
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'companies',
        'description' => 'companies.updated',
    ]);
});

it('archives a company instead of hard deleting it and hides it from active endpoints', function () {
    $admin = instanceCompanyAdmin();
    $company = instanceCompanyRecord('PT Archive');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/instance/companies/{$company->id}")
        ->assertOk()
        ->assertJsonPath('message', 'instance.company.archived');

    expect(Company::withArchived()->find($company->id))->not->toBeNull()
        ->and(Company::withArchived()->find($company->id)?->archived_by)->toBe($admin->id);

    $this->actingAs($admin)
        ->getJson("/api/v1/instance/companies/{$company->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'companies',
        'description' => 'companies.archived',
    ]);
});

function instanceCompanyAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Platform Administrator',
        'email' => uniqid('platform.admin.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceCompanyRecord(string $name): Company
{
    return Company::create([
        'name' => $name,
        'legal_name' => $name.' Legal',
        'slug' => Str::slug($name),
    ]);
}

/**
 * @param  array<int, string>  $permissions
 */
function instanceCompanyUser(Company $company, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('company.user.', true).'@example.test',
        'password' => 'password',
    ]);
    $role = Role::create([
        'company_id' => $company->id,
        'code' => uniqid('company_role_', false),
        'name' => 'Company Role',
    ]);

    foreach ($permissions as $code) {
        [$module, $action] = explode('.', $code, 2);
        $permission = Permission::create([
            'company_id' => $company->id,
            'code' => $code,
            'module' => $module,
            'action' => $action,
            'name' => $code,
        ]);

        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function instanceCompanyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'PT Instance',
        'legalName' => 'PT Instance Legal',
        'address' => 'Jl. Platform 1',
        'city' => 'Jakarta',
        'timezone' => 'Asia/Jakarta',
        'dateFormat' => 'DD/MM/YYYY',
        'languageDefault' => 'id',
        'sessionDurationHours' => 8,
        'loginLockoutAttempts' => 3,
        'loginLockoutMinutes' => 15,
    ], $overrides);
}
