<?php

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeStateMachine;
use App\Domain\Employee\EmployeeStatus;
use App\Jobs\Employee\SendApprovalResultNotificationJob;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ApproveEmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeStateMachine $stateMachine
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(Employee $employee): Employee
    {
        Gate::authorize('employee.approve');
        $this->ensureAssignedApprover($employee);
        $this->ensureValidTransition($employee);

        $updated = DB::transaction(function () use ($employee): Employee {
            $companyId = (int) $employee->getAttribute('company_id');

            return $this->employees->update($employee, [
                'employee_number' => $this->employees->generateEmployeeNumber($companyId),
                'status' => EmployeeStatus::Active->value,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);
        });

        $this->employees->createSystemLog($updated, 'Karyawan diaktifkan oleh '.$this->actorName());
        SendApprovalResultNotificationJob::dispatch((int) $updated->getKey(), EmployeeStatus::Active->value);

        return $updated;
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureAssignedApprover(Employee $employee): void
    {
        if ((int) $employee->getAttribute('approver_id') !== (int) Auth::id()) {
            throw new AuthorizationException('employee.error.approve_not_approver');
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureValidTransition(Employee $employee): void
    {
        try {
            $this->stateMachine->assertCanTransition(
                EmployeeStatus::from((string) $employee->getAttribute('status')),
                EmployeeStatus::Active
            );
        } catch (DomainException) {
            throw ValidationException::withMessages(['status' => ['employee.transition.invalid']]);
        }
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'System';
    }
}
