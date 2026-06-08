<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
});

it('allows users through dynamic permissions registered on the gate', function () {
    $user = User::create([
        'company_id' => $this->company->id,
        'name' => 'HR Staff',
        'email' => 'hr.staff@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $this->company->id,
        'code' => 'hr_staff',
        'name' => 'HR Staff',
    ]);

    $permission = Permission::create([
        'company_id' => $this->company->id,
        'code' => 'employee.view',
        'module' => 'employee',
        'action' => 'view',
        'name' => 'View Employee',
    ]);

    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    expect(Gate::forUser($user)->allows('employee.view'))->toBeTrue();
    expect(Gate::forUser($user)->allows('employee.archive'))->toBeFalse();
});

it('returns forbidden when a user does not have the required permission', function () {
    Route::get('/test-rbac-protected-route', function () {
        Gate::authorize('employee.archive');

        return response()->json(['success' => true]);
    })->middleware('auth:sanctum');

    $user = User::create([
        'company_id' => $this->company->id,
        'name' => 'HR Staff',
        'email' => 'forbidden.hr.staff@saneng.co.id',
        'password' => 'password',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/test-rbac-protected-route')
        ->assertForbidden();
});

it('allows system admin role for permission-style abilities', function () {
    $user = User::create([
        'company_id' => $this->company->id,
        'name' => 'Platform Administrator',
        'email' => 'admin@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $this->company->id,
        'code' => 'system_admin',
        'name' => 'Platform Administrator',
    ]);

    $user->roles()->attach($role->id);

    expect(Gate::forUser($user)->allows('settings.update'))->toBeTrue();
    expect(Gate::forUser($user)->allows('employee.view_sensitive'))->toBeTrue();
});
