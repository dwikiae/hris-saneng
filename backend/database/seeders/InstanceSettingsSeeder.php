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
        'timezone' => 'Asia/Jakarta',
        'language' => 'id',
        'date_format' => 'DD/MM/YYYY',
        'session_duration_hours' => '8',
        'auto_logout' => 'true',
        'lockout_attempts' => '3',
        'lockout_duration_minutes' => '15',
        'storage_driver' => '',
        'storage_endpoint' => '',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_name' => '',
        'smtp_from_email' => '',
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
