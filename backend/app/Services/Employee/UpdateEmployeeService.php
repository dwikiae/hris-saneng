<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class UpdateEmployeeService
{
    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        Gate::authorize('employee.update');

        $payload = $this->employeePayload($employee, $data);

        return $this->employees->update($employee, array_merge($payload, [
            'updated_by' => Auth::id(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function employeePayload(Employee $employee, array $data): array
    {
        if (array_key_exists('employee_number', $data) && ! Gate::allows('employee.override_number')) {
            throw new InvalidArgumentException('employee.employee_number_locked');
        }

        $contractType = $data['contract_type'] ?? null;
        unset($data['contract_type'], $data['consent_at'], $data['consent_by']);

        if (empty($data['employment_type_id'])) {
            $this->mapContractType($data, (int) $employee->getAttribute('company_id'), $contractType);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapContractType(array &$payload, int $companyId, mixed $contractType): void
    {
        if (! in_array($contractType, [EmployeeContract::TYPE_PKWT, EmployeeContract::TYPE_PKWTT], true)) {
            return;
        }

        $employmentTypeId = $this->employees->employmentTypeIdForContractType($companyId, (string) $contractType);

        if ($employmentTypeId === null) {
            throw new InvalidArgumentException('employee.employment_type_not_found');
        }

        $payload['employment_type_id'] = $employmentTypeId;
    }
}
