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

it('returns 200 with token on valid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'login.success')
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'language_preference', 'force_password_reset', 'permissions', 'roles']]])
        ->assertJsonPath('data.user.permissions', [])
        ->assertJsonPath('data.user.roles', []);
});

it('returns permission codes on valid login', function () {
    $role = Role::create([
        'company_id' => $this->user->company_id,
        'code' => 'hr_staff',
        'name' => 'HR Staff',
    ]);

    $permission = Permission::create([
        'company_id' => $this->user->company_id,
        'code' => 'employee.view',
        'module' => 'employee',
        'action' => 'view',
        'name' => 'View Employee',
    ]);

    $role->permissions()->attach($permission->id);
    $this->user->roles()->attach($role->id);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.permissions', ['employee.view'])
        ->assertJsonPath('data.user.roles.0.code', 'hr_staff');
});

it('returns platform settings permission for instance admin login', function () {
    $this->user->update([
        'company_id' => null,
        'name' => 'System Administrator',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.permissions', ['platform.settings'])
        ->assertJsonPath('data.user.roles', []);
});

it('returns 401 for unknown email', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@test.com',
        'password' => 'Admin@1234',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'login.failed');
});

it('returns 401 for wrong password', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'WrongPassword',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'login.failed');
});

it('increments login_attempts on failed password', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'WrongPassword',
    ]);

    $this->user->refresh();
    expect($this->user->login_attempts)->toBe(1);
});

it('locks account after 5 consecutive failed attempts', function () {
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@saneng.co.id',
            'password' => 'WrongPassword',
        ]);
    }

    $this->user->refresh();
    expect($this->user->locked_until)->not->toBeNull();
});

it('returns 423 when account is locked', function () {
    $this->user->update(['locked_until' => now()->addMinutes(15)]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
    ])
        ->assertStatus(423)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'login.locked');
});

it('resets login_attempts on successful login', function () {
    $this->user->update(['login_attempts' => 3]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
    ])->assertOk();

    $this->user->refresh();
    expect($this->user->login_attempts)->toBe(0);
});

it('logout revokes token', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'logout.success');

    // Reset cached Sanctum guard so next request re-validates the token against DB.
    app('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/master-data/departments')
        ->assertUnauthorized();
});

it('validates email is required', function () {
    $this->postJson('/api/v1/auth/login', ['password' => 'Admin@1234'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates password is required', function () {
    $this->postJson('/api/v1/auth/login', ['email' => 'admin@saneng.co.id'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('unauthenticated request to protected route returns 401', function () {
    $this->getJson('/api/v1/master-data/departments')
        ->assertUnauthorized();
});
