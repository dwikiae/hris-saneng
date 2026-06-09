<?php

namespace App\Services\Employee;

use App\Jobs\NotifyApprovalResultJob;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ApproveEmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeNoteService $notes,
        private readonly EmployeeContractService $contracts
    ) {}

    public function submit(Employee $employee): Employee
    {
        Gate::authorize('employee.create');

        return DB::transaction(function () use ($employee): Employee {
            $this->ensureCanTransitionTo($employee, Employee::PENDING);

            $updated = $this->employees->update($employee, [
                'status' => Employee::PENDING,
                'updated_by' => Auth::id(),
            ]);

            $this->notes->storeSystemForEmployee($updated, __('employee.notes.submitted', [
                'actor' => $this->actorName(),
            ]));

            \App\Jobs\NotifyApproverJob::dispatch((int) $updated->getKey());

            return $updated;
        });
    }

    public function approve(Employee $employee): Employee
    {
        Gate::authorize('employee.approve');

        return DB::transaction(function () use ($employee): Employee {
            $this->ensureCanTransitionTo($employee, Employee::ACTIVE);

            $updated = $this->employees->update($employee, [
                'status' => Employee::ACTIVE,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => null,
                'updated_by' => Auth::id(),
            ]);

            $this->contracts->activateLatestDraftForEmployee($updated);

            $this->notes->storeSystemForEmployee($updated, __('employee.notes.approved', [
                'actor' => $this->actorName(),
            ]));

            NotifyApprovalResultJob::dispatch((int) $updated->getKey(), Employee::ACTIVE);

            return $updated;
        });
    }

    public function reject(Employee $employee, ?string $reason = null): Employee
    {
        Gate::authorize('employee.approve');

        if ($reason === null || trim($reason) === '') {
            throw new InvalidArgumentException('employee.rejection_reason_required');
        }

        return DB::transaction(function () use ($employee, $reason): Employee {
            $this->ensureCanTransitionTo($employee, Employee::DRAFT);

            $updated = $this->employees->update($employee, [
                'status' => Employee::DRAFT,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

            $this->notes->storeSystemForEmployee($updated, __('employee.notes.rejected', [
                'actor' => $this->actorName(),
                'reason' => $reason,
            ]));

            NotifyApprovalResultJob::dispatch((int) $updated->getKey(), Employee::DRAFT);

            return $updated;
        });
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
