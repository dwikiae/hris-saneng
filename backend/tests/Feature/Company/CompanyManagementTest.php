<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function milestone8InstanceAdmin(): User
{
    return User::create([
        'company_id' => null,
        'name' => 'Instance Admin',
        'email' => 'instance.admin@example.test',
        'password' => 'password',
    ]);
}

function milestone8CompanyUser(Company $company, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('company.user.', true).'@example.test',
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

it('allows instance admin to manage companies', function () {
    $admin = milestone8InstanceAdmin();
    Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);

    $this->actingAs($admin)
        ->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'company.list');

    $created = $this->actingAs($admin)
        ->postJson('/api/v1/companies', [
            'name' => 'PT Baru',
            'legal_name' => 'PT Baru Legal',
            'language_default' => 'id',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'company.created')
        ->json('data.id');

    $this->actingAs($admin)
        ->putJson("/api/v1/companies/{$created}", [
            'name' => 'PT Baru Updated',
            'legal_name' => 'PT Baru Legal',
            'language_default' => 'en',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'PT Baru Updated');
});

it('keeps company users inside their own company boundary', function () {
    $ownCompany = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $otherCompany = Company::create(['name' => 'PT Other', 'legal_name' => 'PT Other']);
    $actor = milestone8CompanyUser($ownCompany, ['company.view', 'company.update']);

    $this->actingAs($actor)
        ->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownCompany->id);

    $this->actingAs($actor)
        ->getJson("/api/v1/companies/{$otherCompany->id}")
        ->assertNotFound();
});

it('blocks company endpoints without backend permission', function () {
    $company = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $actor = milestone8CompanyUser($company, []);

    $this->actingAs($actor)
        ->getJson('/api/v1/companies')
        ->assertForbidden();
});
