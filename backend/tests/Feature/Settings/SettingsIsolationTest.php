<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\InstanceSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('keeps company settings scoped to the authenticated company', function () {
    $ownCompany = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $otherCompany = Company::create(['name' => 'PT Other', 'legal_name' => 'PT Other']);
    $actor = milestone10SettingsUser($ownCompany, ['settings.view']);

    CompanySetting::withoutCompanyScope()->create([
        'company_id' => $ownCompany->id,
        'key' => 'smtp_host',
        'value' => 'smtp.own.example',
    ]);
    CompanySetting::withoutCompanyScope()->create([
        'company_id' => $otherCompany->id,
        'key' => 'smtp_host',
        'value' => 'smtp.other.example',
    ]);

    $this->actingAs($actor)
        ->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.smtp_host', 'smtp.own.example')
        ->assertJsonMissing(['smtp.other.example']);
});

it('keeps instance settings instance-level and restricted to instance admin', function () {
    $company = Company::create(['name' => 'PT Own', 'legal_name' => 'PT Own']);
    $companyUser = milestone10SettingsUser($company, ['settings.view']);
    $instanceAdmin = User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Instance Admin',
        'email' => 'instance.admin@example.test',
        'password' => 'password',
    ]);

    InstanceSetting::query()->create([
        'key' => 'default_locale',
        'value' => 'en',
    ]);

    expect(Schema::hasColumn('instance_settings', 'company_id'))->toBeFalse();

    $this->actingAs($companyUser)
        ->getJson('/api/v1/settings/instance')
        ->assertForbidden()
        ->assertJsonPath('message', 'settings.instance_forbidden');

    $this->actingAs($instanceAdmin)
        ->getJson('/api/v1/settings/instance')
        ->assertOk()
        ->assertJsonPath('data.default_locale', 'en');
});

function milestone10SettingsUser(Company $company, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Settings User',
        'email' => uniqid('settings.user.', true).'@example.test',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'code' => uniqid('settings_role_', false),
        'name' => 'Settings Role',
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
