<?php

namespace App\Services\Employee;

use App\Jobs\NotifyApproverJob;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class UpdateEmployeeService
{
    private const CRITICAL_FIELDS = [
        'nik',
        'npwp',
        'bank_account_number',
        'department_id',
        'position_id',
    ];

    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        Gate::authorize('employee.update');

        $requiresApproval = $this->hasCriticalChange($employee, $data);

        if ($requiresApproval) {
            if (! $employee->canTransitionTo(Employee::PENDING)) {
                throw new InvalidArgumentException('employee.invalid_status_transition');
            }

            $data['status'] = Employee::PENDING;
        }

        $updated = $this->employees->update($employee, array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        if ($requiresApproval) {
            NotifyApproverJob::dispatch((int) $updated->getKey());
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasCriticalChange(Employee $employee, array $data): bool
    {
        foreach (self::CRITICAL_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if ($employee->getAttribute($field) !== $data[$field]) {
                return true;
            }
        }

        return false;
    }
}
