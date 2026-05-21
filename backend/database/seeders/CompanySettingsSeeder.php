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
        'smtp_encryption' => 'tls',
        'smtp_from_name' => 'HRIS PT Saneng',
        'password_min_length' => '8',
        'password_require_number' => 'true',
        'password_require_uppercase' => 'false',
        'session_timeout_minutes' => '60',
        'max_login_attempts' => '5',
        'lockout_minutes' => '15',
        'retention_employee_financial_years' => '10',
        'retention_employee_nonfinancial_years' => '5',
        'retention_candidate_years' => '1',
        'retention_audit_log_years' => '2',
        'retention_app_log_days' => '90',
        'ip_whitelist' => '127.0.0.1',
    ];

    public function run(): void
    {
        $company = Company::query()->where('name', 'PT Saneng')->firstOrFail();

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
