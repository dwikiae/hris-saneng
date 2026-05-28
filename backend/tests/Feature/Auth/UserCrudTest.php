<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->company = $company;
    $this->actor = User::create([
        'company_id' => $company->id,
        'name' => 'Admin',
        'email' => 'admin@saneng.co.id',
        'password' => 'Admin@1234',
        'language_preference' => 'id',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => 'user_admin',
        'name' => 'User Admin',
    ]);

    foreach (['user.view', 'user.create', 'user.update', 'user.archive'] as $code) {
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

    $this->actor->roles()->attach($role->id);
    $this->actingAs($this->actor);
});

// --- index ---

it('index returns list of users', function () {
    User::create([
        'company_id' => $this->company->id,
        'name' => 'Employee One',
        'email' => 'emp1@saneng.co.id',
        'password' => 'password',
    ]);

    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('index returns 401 when unauthenticated', function () {
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/v1/users')->assertUnauthorized();
});

// --- show ---

it('show returns a single user', function () {
    $this->getJson("/api/v1/users/{$this->actor->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'admin@saneng.co.id');
});

it('show returns 404 for unknown id', function () {
    $this->getJson('/api/v1/users/9999')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

// --- store ---

it('store creates user and sets force_password_reset', function () {
    $this->postJson('/api/v1/users', [
        'name' => 'New Employee',
        'email' => 'new@saneng.co.id',
        'password' => 'Secret@1234',
        'language_preference' => 'en',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'user.created')
        ->assertJsonPath('data.force_password_reset', true);

    expect(User::where('email', 'new@saneng.co.id')->exists())->toBeTrue();
});

it('store fails with duplicate email', function () {
    $this->postJson('/api/v1/users', [
        'name' => 'Duplicate',
        'email' => 'admin@saneng.co.id',
        'password' => 'Secret@1234',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('store fails with missing name', function () {
    $this->postJson('/api/v1/users', [
        'email' => 'noname@saneng.co.id',
        'password' => 'Secret@1234',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('store fails with short password', function () {
    $this->postJson('/api/v1/users', [
        'name' => 'Short Pass',
        'email' => 'short@saneng.co.id',
        'password' => 'short',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// --- update ---

it('update changes name and language_preference', function () {
    $this->putJson("/api/v1/users/{$this->actor->id}", [
        'name' => 'Updated Admin',
        'email' => 'admin@saneng.co.id',
        'language_preference' => 'en',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'user.updated')
        ->assertJsonPath('data.name', 'Updated Admin');

    expect($this->actor->fresh()->language_preference)->toBe('en');
});

it('update fails with duplicate email from another user', function () {
    $other = User::create([
        'company_id' => $this->company->id,
        'name' => 'Other',
        'email' => 'other@saneng.co.id',
        'password' => 'password',
    ]);

    $this->putJson("/api/v1/users/{$this->actor->id}", [
        'name' => 'Admin',
        'email' => 'other@saneng.co.id',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('update returns 404 for unknown id', function () {
    $this->putJson('/api/v1/users/9999', [
        'name' => 'Ghost',
        'email' => 'ghost@saneng.co.id',
    ])
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

// --- archive ---

it('archive soft-archives user', function () {
    $user = User::create([
        'company_id' => $this->company->id,
        'name' => 'To Archive',
        'email' => 'archive@saneng.co.id',
        'password' => 'password',
    ]);

    $this->postJson("/api/v1/users/{$user->id}/archive")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'user.archived');

    expect(User::find($user->id))->toBeNull();
    expect($user->fresh()->archived_at)->not->toBeNull();
});

it('archive returns 404 for unknown id', function () {
    $this->postJson('/api/v1/users/9999/archive')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

// --- restore ---

it('restore brings user back', function () {
    $user = User::create([
        'company_id' => $this->company->id,
        'name' => 'To Restore',
        'email' => 'restore@saneng.co.id',
        'password' => 'password',
    ]);
    $user->archive($this->actor->id);

    $this->postJson("/api/v1/users/{$user->id}/restore")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'user.restored');

    expect(User::find($user->id))->not->toBeNull();
});
