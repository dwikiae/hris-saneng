<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\InstanceSetting;
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
