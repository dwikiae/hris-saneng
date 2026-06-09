<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Modules\Karyawan\Repositories\Contracts\EmployeeModuleSettingsRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CreateEmployeeService
{
    private const DEFAULT_NUMBER_FORMAT = 'EMP-{SEQ}';

    /**
     * @var list<string>
     */
    private array $employeeFields = [
        'name',
        'nickname',
        'email',
        'phone',
        'address',
        'province_id',
        'city_id',
        'domicile_address',
        'domicile_province_id',
        'domicile_city_id',
        'birth_date',
        'birth_place',
        'country_of_birth',
        'gender',
        'religion_id',
        'marital_status_id',
        'blood_type_id',
        'nationality',
        'passport_number',
        'department_id',
        'position_id',
        'employment_type_id',
        'employee_level_id',
        'work_location_id',
        'supervisor_id',
        'join_date',
        'probation_end_date',
        'end_date',
        'nik',
        'npwp',
        'bank_name',
        'bank_account_number',
        'bank_account_holder_name',
        'salary',
        'allowances',
        'deductions',
        'approver_id',
    ];

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeModuleSettingsRepositoryInterface $settings,
        private readonly EmployeeContractService $contracts,
        private readonly EmployeeEmergencyContactService $emergencyContacts
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        Gate::authorize('employee.create');

        $companyId = (int) config('app.company_id');
        $contract = $data['contract'] ?? null;
        $emergencyContact = $data['emergency_contact'] ?? null;

        $employee = DB::transaction(function () use ($data, $companyId, $contract, $emergencyContact): Employee {
            $payload = $this->employeePayload($data, $companyId);

            $employee = $this->employees->create($payload);

            if (is_array($contract)) {
                $this->contracts->storeForEmployee($employee, $contract);
            }

            if (is_array($emergencyContact)) {
                $this->emergencyContacts->storeForEmployee($employee, $emergencyContact);
            }

            return $employee->refresh();
        });

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function employeePayload(array $data, int $companyId): array
    {
        $payload = array_intersect_key($data, array_flip($this->employeeFields));
        $this->mapContractType($payload, $data, $companyId);

        return array_merge($payload, [
            'company_id' => $companyId,
            'employee_number' => $this->generateEmployeeNumber($companyId),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'consent_at' => now(),
            'consent_by' => Auth::id(),
            'status' => Employee::DRAFT,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    private function mapContractType(array &$payload, array $data, int $companyId): void
    {
        if (! empty($payload['employment_type_id'])) {
            return;
        }

        $contractType = $data['contract_type'] ?? ($data['contract']['contract_type'] ?? null);

        if (! in_array($contractType, [EmployeeContract::TYPE_PKWT, EmployeeContract::TYPE_PKWTT], true)) {
            return;
        }

        $employmentTypeId = $this->employees->employmentTypeIdForContractType($companyId, (string) $contractType);

        if ($employmentTypeId === null) {
            throw new InvalidArgumentException('employee.employment_type_not_found');
        }

        $payload['employment_type_id'] = $employmentTypeId;
    }

    private function generateEmployeeNumber(int $companyId): string
    {
        $format = $this->numberFormat($companyId);
        $sequence = $this->nextSequence($companyId, $format);

        return $this->renderNumber($format, $sequence);
    }

    private function numberFormat(int $companyId): string
    {
        $settings = $this->settings->valuesForCompany($companyId);
        $format = trim((string) ($settings['employee_number_format'] ?? ''));

        return $format === '' ? self::DEFAULT_NUMBER_FORMAT : $format;
    }

    private function nextSequence(int $companyId, string $format): int
    {
        $max = 0;

        foreach ($this->employees->employeeNumbersForCompanyIncludingArchived($companyId) as $number) {
            $sequence = $this->sequenceFromFormattedNumber($format, $number)
                ?? $this->sequenceFromLastNumericSegment($number);

            if ($sequence !== null) {
                $max = max($max, $sequence);
            }
        }

        return $max + 1;
    }

    private function sequenceFromFormattedNumber(string $format, string $number): ?int
    {
        $regex = preg_quote($format, '/');
        $regex = preg_replace('/\\\\\{SEQ(?:\d+)?\\\\\}/', '(?<seq>\d+)', $regex);
        $regex = str_replace(['\{YYYY\}', '\{MM\}', '\{DD\}'], ['\d{4}', '\d{2}', '\d{2}'], (string) $regex);

        if (preg_match('/^'.$regex.'$/', $number, $matches) !== 1) {
            return null;
        }

        return isset($matches['seq']) ? (int) $matches['seq'] : null;
    }

    private function sequenceFromLastNumericSegment(string $number): ?int
    {
        if (preg_match_all('/\d+/', $number, $matches) === 0) {
            return null;
        }

        return (int) end($matches[0]);
    }

    private function renderNumber(string $format, int $sequence): string
    {
        $now = now();
        $rendered = str_replace([
            '{YYYY}',
            '{MM}',
            '{DD}',
        ], [
            $now->format('Y'),
            $now->format('m'),
            $now->format('d'),
        ], $format);

        return preg_replace_callback('/\{SEQ(\d*)\}/', function (array $matches) use ($sequence): string {
            $padding = $matches[1] === '' ? 3 : (int) $matches[1];

            return str_pad((string) $sequence, max(1, $padding), '0', STR_PAD_LEFT);
        }, $rendered) ?? $rendered;
    }
}
