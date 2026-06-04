<?php

namespace App\Modules\Karyawan\Repositories\Eloquent;

use App\Modules\Karyawan\Models\EmployeeModuleSetting;
use App\Modules\Karyawan\Repositories\Contracts\EmployeeModuleSettingsRepositoryInterface;

class EmployeeModuleSettingsRepository implements EmployeeModuleSettingsRepositoryInterface
{
    /**
     * @return array<string, string>
     */
    public function valuesForCompany(int $companyId): array
    {
        return EmployeeModuleSetting::query()
            ->forCompany($companyId)
            ->get()
            ->mapWithKeys(fn (EmployeeModuleSetting $setting): array => [
                (string) $setting->getAttribute('key') => (string) $setting->getAttribute('value'),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function sync(int $companyId, array $values): void
    {
        foreach ($values as $key => $value) {
            EmployeeModuleSetting::query()->withoutCompanyScope()->updateOrCreate(
                ['company_id' => $companyId, 'key' => $key],
                ['value' => (string) $value]
            );
        }
    }
}
