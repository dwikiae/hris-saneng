<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Company;
use App\Models\Department;
use App\Modules\Karyawan\Domain\EmployeeNumberTokenEngine;
use App\Modules\Karyawan\Repositories\Contracts\EmployeeModuleSettingsRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CreateEmployeeService
{
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
        private readonly EmployeeEmergencyContactService $emergencyContacts,
        private readonly EmployeeNumberTokenEngine $numberTokenEngine
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
            'employee_number' => $this->generateEmployeeNumber($payload, $data, $companyId),
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

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    private function generateEmployeeNumber(array $payload, array $data, int $companyId): string
    {
        $format = $this->numberFormat($companyId);
        $employee = new Employee(array_merge($payload, [
            'company_id' => $companyId,
        ]));
        $employee->setRelation('department', $this->departmentForPayload($payload, $companyId));
        $employee->setAttribute(
            EmployeeNumberTokenEngine::TEMP_CONTRACT_TYPE_ATTRIBUTE,
            $data['contract_type'] ?? ($data['contract']['contract_type'] ?? null)
        );

        /** @var Company $company */
        $company = Company::query()->findOrFail($companyId);

        return $this->numberTokenEngine->resolve($format, $employee, $company);
    }

    private function numberFormat(int $companyId): string
    {
        $settings = $this->settings->valuesForCompany($companyId);
        $format = trim((string) ($settings['employee_number_format'] ?? ''));

        return $this->numberTokenEngine->normalizeFormat($format);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function departmentForPayload(array $payload, int $companyId): ?Department
    {
        if (empty($payload['department_id'])) {
            return null;
        }

        return Department::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->find((int) $payload['department_id']);
    }
}
