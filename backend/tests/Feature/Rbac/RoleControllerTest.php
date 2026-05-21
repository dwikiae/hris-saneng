<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->actor = User::create([
        'company_id' => $this->company->id,
        'name' => 'Admin',
        'email' => 'admin@saneng.co.id',
        'password' => 'password',
    ]);
    $this->actingAs($this->actor);
});

it('lists roles', function () {
    Role::create([
        'company_id' => $this->company->id,
        'code' => 'hr_staff',
        'name' => 'HR Staff',
    ]);

    $this->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

it('creates and shows a role', function () {
    $response = $this->postJson('/api/v1/roles', [
        'code' => 'hr_manager',
        'name' => 'HR Manager',
        'description' => 'Full HR access',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.code', 'hr_manager');

    $this->getJson('/api/v1/roles/'.$response->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.name', 'HR Manager');
});

it('updates a role', function () {
    $role = Role::create([
        'company_id' => $this->company->id,
        'code' => 'dept_manager',
        'name' => 'Dept Manager',
    ]);

    $this->putJson("/api/v1/roles/{$role->id}", [
        'name' => 'Department Manager',
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Department Manager')
        ->assertJsonPath('data.is_active', false);
});

it('syncs role permissions using replacement semantics', function () {
    $role = Role::create([
        'company_id' => $this->company->id,
        'code' => 'hr_staff',
        'name' => 'HR Staff',
    ]);

    $view = Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view',
        'module' => 'employee',
        'action' => 'view',
        'name' => 'View Employee',
    ]);

    $update = Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.update',
        'module' => 'employee',
        'action' => 'update',
        'name' => 'Update Employee',
    ]);

    $role->permissions()->attach($view->id);

    $this->postJson("/api/v1/roles/{$role->id}/sync-permissions", [
        'permission_ids' => [$update->id],
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.permissions')
        ->assertJsonPath('data.permissions.0.id', $update->id);

    expect($role->fresh()->permissions()->pluck('permissions.id')->all())->toBe([$update->id]);
});
