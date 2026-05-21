<?php

use App\Models\Company;
use App\Models\Permission;
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

it('lists permissions', function () {
    Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view',
        'module' => 'employee',
        'action' => 'view',
        'name' => 'View Employee',
    ]);

    $this->getJson('/api/v1/permissions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

it('filters permissions by module', function () {
    Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view',
        'module' => 'employee',
        'action' => 'view',
        'name' => 'View Employee',
    ]);

    Permission::create([
        'company_id' => $this->company->id,
        'code' => 'settings.view',
        'module' => 'settings',
        'action' => 'view',
        'name' => 'View Settings',
    ]);

    $this->getJson('/api/v1/permissions?module=employee')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.module', 'employee');
});
