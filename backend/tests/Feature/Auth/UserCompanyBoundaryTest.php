<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function milestone8AuthInstanceAdmin(): User
{
    return User::create([
        'company_id' => null,
        'name' => 'Instance Admin',
        'email' => 'instance.admin.auth@example.test',
        'password' => 'password',
    ]);
}

function milestone8AuthCompanyUser(Company $company, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('auth.company.user.', true).'@example.test',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => uniqid('manager_', false),
        'name' => 'Manager',
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

it('allows instance admin to create the first company user', function () {
    $admin = milestone8AuthInstanceAdmin();
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $manager = Role::create(['company_id' => $company->id, 'code' => 'manager', 'name' => 'Manager']);

    $this->actingAs($admin)
        ->postJson("/api/v1/companies/{$company->id}/users", [
            'name' => 'First Manager',
            'email' => 'first.manager@example.test',
            'password' => 'Password123',
            'role_ids' => [$manager->id],
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'user.created')
        ->assertJsonPath('data.company_id', $company->id)
        ->assertJsonPath('data.roles.0.code', 'manager');

    expect(User::where('email', 'first.manager@example.test')->first()?->company_id)->toBe($company->id);
});

it('scopes user listing to the current company user', function () {
    $ownCompany = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $otherCompany = Company::create(['name' => 'PT Other', 'legal_name' => 'PT Other']);
    $actor = milestone8AuthCompanyUser($ownCompany, ['user.view']);

    User::create(['company_id' => $ownCompany->id, 'name' => 'Own User', 'email' => 'own@example.test', 'password' => 'password']);
    User::create(['company_id' => $otherCompany->id, 'name' => 'Other User', 'email' => 'other@example.test', 'password' => 'password']);

    $this->actingAs($actor)
        ->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonPath('message', 'user.list')
        ->assertJsonMissing(['email' => 'other@example.test'])
        ->assertJsonFragment(['email' => 'own@example.test']);
});

it('prevents company users from creating users in another company', function () {
    $ownCompany = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $otherCompany = Company::create(['name' => 'PT Other', 'legal_name' => 'PT Other']);
    $actor = milestone8AuthCompanyUser($ownCompany, ['user.create']);

    $this->actingAs($actor)
        ->postJson("/api/v1/companies/{$otherCompany->id}/users", [
            'name' => 'Cross User',
            'email' => 'cross@example.test',
            'password' => 'Password123',
        ])
        ->assertForbidden();
});

it('prevents cross company role assignment', function () {
    $ownCompany = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $otherCompany = Company::create(['name' => 'PT Other', 'legal_name' => 'PT Other']);
    $actor = milestone8AuthCompanyUser($ownCompany, ['user.assign_role']);
    $target = User::create(['company_id' => $ownCompany->id, 'name' => 'Target', 'email' => 'target@example.test', 'password' => 'password']);
    $otherRole = Role::create(['company_id' => $otherCompany->id, 'code' => 'staff', 'name' => 'Staff']);

    $this->actingAs($actor)
        ->postJson("/api/v1/users/{$target->id}/roles", [
            'role_ids' => [$otherRole->id],
        ])
        ->assertUnprocessable();
});
