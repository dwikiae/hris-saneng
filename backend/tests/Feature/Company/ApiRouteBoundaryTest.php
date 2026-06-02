<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires company context on company-scoped private routes for instance admins', function () {
    $instanceAdmin = routeBoundaryInstanceAdmin();

    $this->actingAs($instanceAdmin)
        ->getJson('/api/v1/settings')
        ->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'company.context.required',
        ]);
});

it('resolves company-scoped private routes from a company user without requiring a header', function () {
    $company = Company::create(['name' => 'PT Boundary', 'legal_name' => 'PT Boundary']);
    $user = routeBoundaryCompanyUser($company, ['settings.view']);

    CompanySetting::withoutCompanyScope()->create([
        'company_id' => $company->id,
        'key' => 'smtp_host',
        'value' => 'smtp.boundary.example',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.smtp_host', 'smtp.boundary.example');
});

it('keeps instance-level private routes outside the company context middleware', function () {
    $instanceAdmin = routeBoundaryInstanceAdmin();

    $this->actingAs($instanceAdmin)
        ->getJson('/api/v1/settings/instance')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('keeps company management routes outside the company context middleware', function () {
    $instanceAdmin = routeBoundaryInstanceAdmin();

    $this->actingAs($instanceAdmin)
        ->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonPath('success', true);
});

function routeBoundaryInstanceAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Instance Admin',
        'email' => uniqid('instance.admin.', true).'@example.test',
        'password' => 'password',
    ]);
}

/**
 * @param  array<int, string>  $permissions
 */
function routeBoundaryCompanyUser(Company $company, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('company.user.', true).'@example.test',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => uniqid('boundary_role_', false),
        'name' => 'Boundary Role',
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
