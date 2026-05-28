<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    /** @var array<string, string> */
    private const DEFAULT_SETTINGS = [
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_from_name' => 'HRIS PT Saneng',
        'smtp_from_address' => '',
        'password_min_length' => '8',
        'password_requires_number' => 'true',
        'password_requires_uppercase' => 'true',
        'password_requires_symbol' => 'false',
        'session_lifetime_minutes' => '120',
        'max_login_attempts' => '5',
        'lockout_duration_minutes' => '15',
        'retention_employee_years' => '5',
        'retention_candidate_years' => '1',
        'retention_audit_years' => '2',
        'storage_disk' => 'documents',
        'storage_max_upload_mb' => '10',
    ];

    public function run(): void
    {
        $company = Company::query()
            ->find((int) config('company.default_id', 1))
            ?? Company::query()->orderBy('id')->firstOrFail();

        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            CompanySetting::withoutCompanyScope()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'key' => $key,
                ],
                ['value' => $value]
            );
        }
    }
}
