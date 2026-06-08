<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlatformAdministratorAssignmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds PT Saneng company record', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Company::first()?->name)->toBe('PT Saneng');
});

it('seeds default company settings with correct values', function () {
    $this->seed(DatabaseSeeder::class);

    expect(CompanySetting::withoutCompanyScope()->where('key', 'session_lifetime_minutes')->value('value'))->toBe('120')
        ->and(CompanySetting::where('key', 'max_login_attempts')->value('value'))->toBe('5')
        ->and(CompanySetting::where('key', 'storage_max_upload_mb')->value('value'))->toBe('10');
});

it('seeds admin user with force_password_reset true', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::withoutCompanyScope()->where('email', 'admin@saneng.co.id')->firstOrFail();

    expect($user->force_password_reset)->toBeTrue();
});

it('admin user password is correctly hashed', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::withoutCompanyScope()->where('email', 'admin@saneng.co.id')->firstOrFail();

    expect(Hash::check('Admin@12345', $user->password))->toBeTrue();
});

it('assigns platform administrator to PT Saneng idempotently', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(PlatformAdministratorAssignmentSeeder::class);
    $this->seed(PlatformAdministratorAssignmentSeeder::class);

    $user = User::withoutCompanyScope()->where('email', 'admin@saneng.co.id')->firstOrFail();
    $role = Role::withoutCompanyScope()->where('code', 'system_admin')->firstOrFail();

    expect($role->name)->toBe('Platform Administrator')
        ->and($user->roles()->where('roles.id', $role->id)->count())->toBe(1);
});
