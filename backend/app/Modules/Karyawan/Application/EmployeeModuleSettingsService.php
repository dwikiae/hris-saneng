<?php

namespace App\Modules\Karyawan\Application;

use App\Modules\Karyawan\Repositories\Contracts\EmployeeModuleSettingsRepositoryInterface;

class EmployeeModuleSettingsService
{
    /**
     * @var array<string, int|string>
     */
    private array $defaults = [
        'employee_number_format' => 'EMP-{YYYY}-{SEQ4}',
        'probation_days' => 90,
        'contract_expiry_notify_days' => 30,
        'pkwt_max_months' => 60,
    ];

    public function __construct(private readonly EmployeeModuleSettingsRepositoryInterface $settings) {}

    /**
     * @return array<string, int|string>
     */
    public function get(int $companyId): array
    {
        return $this->normalize(array_merge($this->defaults, $this->settings->valuesForCompany($companyId)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, int|string>
     */
    public function update(int $companyId, array $payload): array
    {
        $values = array_intersect_key($payload, $this->defaults);
        $this->settings->sync($companyId, $values);

        return $this->get($companyId);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, int|string>
     */
    private function normalize(array $values): array
    {
        foreach (['probation_days', 'contract_expiry_notify_days', 'pkwt_max_months'] as $key) {
            $values[$key] = (int) $values[$key];
        }

        $values['employee_number_format'] = (string) $values['employee_number_format'];

        return $values;
    }
}
