<?php

namespace App\Http\Resources;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstanceCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = $this->settings();
        $activeModuleCodes = $this->jsonSetting($settings, 'active_module_codes');

        return [
            'id' => $this->resource->getAttribute('id'),
            'name' => $this->resource->getAttribute('name'),
            'legalName' => $this->resource->getAttribute('legal_name'),
            'slug' => $this->resource->getAttribute('slug'),
            'logoPath' => $this->resource->getAttribute('logo_path'),
            'logoUrl' => $this->resource->getAttribute('logo_path'),
            'tagline' => $settings['tagline'] ?? null,
            'companyType' => $settings['company_type'] ?? null,
            'industry' => $settings['industry'] ?? null,
            'foundedDate' => $settings['founded_date'] ?? null,
            'address' => $this->resource->getAttribute('address'),
            'city' => $this->resource->getAttribute('city'),
            'province' => $settings['province'] ?? null,
            'postalCode' => $settings['postal_code'] ?? null,
            'phone' => $this->resource->getAttribute('phone'),
            'email' => $this->resource->getAttribute('email'),
            'website' => $this->resource->getAttribute('website'),
            'hrPicName' => $settings['hr_pic_name'] ?? null,
            'hrPicPhone' => $settings['hr_pic_phone'] ?? null,
            'hrPicEmail' => $settings['hr_pic_email'] ?? null,
            'npwp' => $this->resource->getAttribute('npwp'),
            'nibOrSiup' => $settings['nib_or_siup'] ?? null,
            'bpjsKetenagakerjaan' => $settings['bpjs_ketenagakerjaan'] ?? null,
            'bpjsKesehatan' => $settings['bpjs_kesehatan'] ?? null,
            'wlkpNumber' => $settings['wlkp_number'] ?? null,
            'directorName' => $settings['director_name'] ?? null,
            'timezone' => $this->resource->getAttribute('timezone'),
            'dateFormat' => $this->resource->getAttribute('date_format'),
            'languageDefault' => $this->resource->getAttribute('language_default'),
            'smtpHost' => $settings['smtp_host'] ?? null,
            'smtpPort' => $settings['smtp_port'] ?? null,
            'smtpUsername' => $settings['smtp_username'] ?? null,
            'smtpPasswordMasked' => $settings['smtp_password_masked'] ?? null,
            'smtpFromName' => $settings['smtp_from_name'] ?? null,
            'smtpFromEmail' => $settings['smtp_from_email'] ?? null,
            'loginLockoutAttempts' => $this->integerSetting($settings, 'login_lockout_attempts'),
            'loginLockoutMinutes' => $this->integerSetting($settings, 'login_lockout_minutes'),
            'sessionDurationHours' => $this->integerSetting($settings, 'session_duration_hours'),
            'employeeCount' => (int) ($this->resource->getAttribute('employees_count') ?? 0),
            'status' => $this->resource->getAttribute('archived_at') === null ? 'active' : 'archived',
            'activeModules' => array_map(
                fn (string $code): array => ['code' => $code, 'name' => $code, 'isActive' => true],
                $activeModuleCodes
            ),
            'createdAt' => $this->resource->getAttribute('created_at'),
            'updatedAt' => $this->resource->getAttribute('updated_at'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        if (! $this->resource->relationLoaded('settings')) {
            return [];
        }

        return $this->resource->getRelation('settings')
            ->mapWithKeys(fn (CompanySetting $setting): array => [
                (string) $setting->getAttribute('key') => $setting->getAttribute('value'),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, string>
     */
    private function jsonSetting(array $settings, string $key): array
    {
        $value = $settings[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, is_string(...)));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function integerSetting(array $settings, string $key): ?int
    {
        if (! array_key_exists($key, $settings) || $settings[$key] === null || $settings[$key] === '') {
            return null;
        }

        return (int) $settings[$key];
    }
}
