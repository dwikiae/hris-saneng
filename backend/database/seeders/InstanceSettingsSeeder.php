<?php

namespace Database\Seeders;

use App\Models\InstanceSetting;
use Illuminate\Database\Seeder;

class InstanceSettingsSeeder extends Seeder
{
    /** @var array<string, string|null> */
    private const DEFAULT_SETTINGS = [
        'setup_completed' => 'false',
        'setup_completed_at' => null,
        'setup_completed_by' => null,
        'default_locale' => 'id',
        'mandatory_modules_installed' => 'false',
        'installed_mandatory_modules' => '[]',
        'storage_driver' => '',
        'storage_endpoint' => '',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            InstanceSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
