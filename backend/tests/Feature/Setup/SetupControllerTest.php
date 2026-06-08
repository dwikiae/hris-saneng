<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\InstanceSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports setup status without authentication', function () {
    $this->getJson('/api/v1/setup/status')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'setup.status')
        ->assertJsonPath('data.setup_completed', false)
        ->assertJsonPath('data.has_company', false)
        ->assertJsonPath('data.has_instance_admin', false);
});

it('completes first-time setup with company, instance admin, and baseline settings', function () {
    $this->postJson('/api/v1/setup/complete', milestone10SetupPayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'setup.completed')
        ->assertJsonPath('data.company_slug', 'pt-saneng')
        ->assertJsonPath('data.mandatory_modules.0', 'karyawan')
        ->assertJsonMissing(['StrongPass123']);

    $company = Company::query()->first();
    $admin = User::withoutCompanyScope()->whereNull('company_id')->first();

    expect($company)->not->toBeNull()
        ->and($admin)->not->toBeNull()
        ->and($admin?->company_id)->toBeNull()
        ->and(InstanceSetting::query()->where('key', 'setup_completed')->value('value'))->toBe('true')
        ->and(CompanySetting::withoutCompanyScope()
            ->where('company_id', $company?->id)
            ->where('key', 'lockout_duration_minutes')
            ->value('value'))->toBe('15');

    $role = Role::withoutCompanyScope()
        ->where('company_id', $company?->id)
        ->where('code', 'system_admin')
        ->firstOrFail();

    $this->assertDatabaseHas('user_roles', [
        'user_id' => $admin?->id,
        'role_id' => $role->id,
    ]);
});

it('blocks setup completion after setup is already completed', function () {
    InstanceSetting::query()->create([
        'key' => 'setup_completed',
        'value' => 'true',
    ]);

    $this->postJson('/api/v1/setup/complete', milestone10SetupPayload())
        ->assertStatus(409)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'setup.already_completed');
});

/**
 * @return array<string, mixed>
 */
function milestone10SetupPayload(): array
{
    return [
        'company' => [
            'name' => 'PT Saneng',
            'legal_name' => 'PT Saneng',
            'slug' => null,
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'DD/MM/YYYY',
            'language_default' => 'id',
        ],
        'admin' => [
            'name' => 'Instance Admin',
            'email' => 'instance.admin@example.test',
            'password' => 'StrongPass123',
            'language_preference' => 'id',
        ],
    ];
}
