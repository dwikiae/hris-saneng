<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

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

it('requires settings view permission', function () {
    $this->getJson('/api/v1/settings')->assertForbidden();
});

it('lists default settings and masks stored smtp password', function () {
    grantSettingsPermission($this->actor, $this->company, 'settings.view');

    CompanySetting::create([
        'company_id' => $this->company->id,
        'key' => 'smtp_password',
        'value' => Crypt::encryptString('secret-password'),
    ]);

    $this->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.password_min_length', 8)
        ->assertJsonPath('data.password_requires_uppercase', true)
        ->assertJsonPath('data.smtp_port', 587)
        ->assertJsonPath('data.smtp_password', '*****')
        ->assertJsonMissing(['secret-password']);
});

it('updates settings in bulk and encrypts smtp password', function () {
    grantSettingsPermission($this->actor, $this->company, 'settings.view');
    grantSettingsPermission($this->actor, $this->company, 'settings.update');

    $this->putJson('/api/v1/settings', [
        'password_min_length' => 12,
        'password_requires_symbol' => true,
        'smtp_host' => 'smtp.example.test',
        'smtp_password' => 'secret-password',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.password_min_length', 12)
        ->assertJsonPath('data.password_requires_symbol', true)
        ->assertJsonPath('data.smtp_host', 'smtp.example.test')
        ->assertJsonPath('data.smtp_password', '*****')
        ->assertJsonMissing(['secret-password']);

    $storedPassword = CompanySetting::where('key', 'smtp_password')->value('value');

    expect($storedPassword)->not->toBe('secret-password')
        ->and(Crypt::decryptString($storedPassword))->toBe('secret-password');
});

it('requires settings update permission', function () {
    grantSettingsPermission($this->actor, $this->company, 'settings.view');

    $this->putJson('/api/v1/settings', [
        'password_min_length' => 12,
    ])->assertForbidden();
});

function grantSettingsPermission(User $user, Company $company, string $code): void
{
    $role = Role::firstOrCreate(
        [
            'company_id' => $company->id,
            'code' => 'settings_admin',
        ],
        ['name' => 'Settings Admin']
    );

    $permission = Permission::firstOrCreate(
        [
            'company_id' => $company->id,
            'code' => $code,
        ],
        [
            'module' => 'settings',
            'action' => str($code)->after('.')->toString(),
            'name' => $code,
        ]
    );

    $role->permissions()->syncWithoutDetaching($permission->id);
    $user->roles()->syncWithoutDetaching($role->id);
}
