<?php

namespace App\Services\Employee;

use App\Jobs\NotifyApprovalResultJob;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ApproveEmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeNoteService $notes
    ) {}

    public function approve(Employee $employee): Employee
    {
        Gate::authorize('employee.approve');

        $this->ensureCanTransitionTo($employee, Employee::APPROVED);

        $updated = $this->employees->update($employee, [
            'status' => Employee::APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->notes->storeSystemForEmployee($updated, 'Disetujui oleh '.$this->actorName());

        NotifyApprovalResultJob::dispatch((int) $updated->getKey(), Employee::APPROVED);

        return $updated;
    }

    public function reject(Employee $employee, ?string $reason = null): Employee
    {
        Gate::authorize('employee.approve');

        $this->ensureCanTransitionTo($employee, Employee::REJECTED);

        $updated = $this->employees->update($employee, [
            'status' => Employee::REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notes->storeSystemForEmployee($updated, 'Ditolak: '.($reason ?: '-'));

        NotifyApprovalResultJob::dispatch((int) $updated->getKey(), Employee::REJECTED);

        return $updated;
    }

    private function ensureCanTransitionTo(Employee $employee, string $status): void
    {
        if (! $employee->canTransitionTo($status)) {
            throw new InvalidArgumentException('employee.invalid_status_transition');
        }
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user?->getAttribute('name') ?: 'System';
    }
}
