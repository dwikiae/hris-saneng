<?php

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ArchiveEmployeeService
{
    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    /**
     * @throws ValidationException
     */
    public function execute(Employee $employee): void
    {
        Gate::authorize('employee.archive');

        if ($employee->getAttribute('status') !== EmployeeStatus::Pending->value) {
            throw ValidationException::withMessages(['status' => ['employee.error.archive_only_pending']]);
        }

        $this->employees->createSystemLog($employee, 'Karyawan pending diarsipkan oleh '.$this->actorName());
        $this->employees->archive($employee);
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'System';
    }
}
