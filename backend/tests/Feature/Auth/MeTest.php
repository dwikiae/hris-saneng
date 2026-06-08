<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id' => $company->id,
        'name' => 'Test User',
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
        'language_preference' => 'id',
        'force_password_reset' => false,
        'login_attempts' => 0,
    ]);
});
it('returns user data when authenticated', function () {
    $token = $this->user->createToken('test')->plainTextToken;
    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
        ->assertStatus(200)
        ->assertJsonFragment(['email' => 'admin@saneng.co.id'])
        ->assertJsonPath('data.permissions', [])
        ->assertJsonPath('data.roles', []);
});

it('returns permission codes when authenticated', function () {
    $role = Role::create([
        'company_id' => $this->user->company_id,
        'code' => 'hr_staff',
        'name' => 'HR Staff',
    ]);

    $permission = Permission::create([
        'company_id' => $this->user->company_id,
        'code' => 'employee.create',
        'module' => 'employee',
        'action' => 'create',
        'name' => 'Create Employee',
    ]);

    $role->permissions()->attach($permission->id);
    $this->user->roles()->attach($role->id);

    $token = $this->user->createToken('test')->plainTextToken;

    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.permissions', ['employee.create'])
        ->assertJsonPath('data.roles.0.code', 'hr_staff');
});

it('returns platform settings permission for instance admin', function () {
    $this->user->update([
        'company_id' => null,
        'name' => 'System Administrator',
    ]);
    $token = $this->user->createToken('test')->plainTextToken;

    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.permissions', ['platform.settings'])
        ->assertJsonPath('data.roles', []);
});

it('returns assigned company roles for platform administrator', function () {
    $company = Company::query()->firstOrFail();
    $this->user->update([
        'company_id' => null,
        'name' => 'Platform Administrator',
    ]);
    $role = Role::create([
        'company_id' => $company->id,
        'code' => 'system_admin',
        'name' => 'Platform Administrator',
    ]);
    $this->user->roles()->attach($role->id);
    $token = $this->user->createToken('test')->plainTextToken;

    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.roles.0.code', 'system_admin')
        ->assertJsonPath('data.roles.0.name', 'Platform Administrator')
        ->assertJsonPath('data.roles.0.company_id', $company->id)
        ->assertJsonPath('data.roles.0.company_name', 'PT Saneng');
});

it('returns 401 when unauthenticated', function () {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});
it('returns user data when force_password_reset is true so auth can hydrate', function () {
    $this->user->update(['force_password_reset' => true]);
    $token = $this->user->createToken('test')->plainTextToken;
    $this->getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('data.force_password_reset', true)
        ->assertJsonPath('data.permissions', [])
        ->assertJsonPath('data.roles', []);
});
