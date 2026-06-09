<?php

namespace App\Modules\Karyawan\Application;

use App\Models\Company;
use App\Modules\Karyawan\Domain\EmployeeNumberTokenEngine;
use App\Modules\Karyawan\Repositories\Contracts\EmployeeModuleSettingsRepositoryInterface;

class EmployeeModuleSettingsService
{
    /**
     * @var array<string, int|string>
     */
    private array $defaults = [
        'employee_number_format' => EmployeeNumberTokenEngine::DEFAULT_FORMAT,
        'probation_days' => 90,
        'contract_expiry_notify_days' => 30,
        'pkwt_max_months' => 60,
    ];

    public function __construct(
        private readonly EmployeeModuleSettingsRepositoryInterface $settings,
        private readonly EmployeeNumberTokenEngine $numberTokenEngine
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(int $companyId): array
    {
        return $this->withNumberFormatMetadata(
            $this->normalize(array_merge($this->defaults, $this->settings->valuesForCompany($companyId)))
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(int $companyId, array $payload): array
    {
        if (array_key_exists('number_format', $payload) && ! array_key_exists('employee_number_format', $payload)) {
            $payload['employee_number_format'] = $payload['number_format'];
        }

        $values = array_intersect_key($payload, $this->defaults);
        if (array_key_exists('employee_number_format', $values)) {
            $values['employee_number_format'] = $this->numberTokenEngine->normalizeFormat((string) $values['employee_number_format']);
            $this->numberTokenEngine->validate((string) $values['employee_number_format']);
        }

        $this->settings->sync($companyId, $values);

        return $this->get($companyId);
    }

    /**
     * @return array{preview: string, next_sequence: int, tokens_used: array<int, string>, tokens_available: array<int, array<string, mixed>>}
     */
    public function preview(int $companyId, string $format): array
    {
        /** @var Company $company */
        $company = Company::query()->findOrFail($companyId);

        return $this->numberTokenEngine->preview($format, $company);
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

        $values['employee_number_format'] = $this->numberTokenEngine->normalizeFormat((string) $values['employee_number_format']);

        return $values;
    }

    /**
     * @param  array<string, int|string>  $values
     * @return array<string, mixed>
     */
    private function withNumberFormatMetadata(array $values): array
    {
        $values['number_format'] = $values['employee_number_format'];
        $values['number_format_tokens_available'] = $this->numberTokenEngine->tokensAvailable();

        return $values;
    }
}
