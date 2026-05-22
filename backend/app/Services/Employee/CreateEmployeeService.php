<?php

namespace App\Services\Employee;

use App\Jobs\NotifyApproverJob;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CreateEmployeeService
{
    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        Gate::authorize('employee.create');

        if (empty($data['consent_at'])) {
            throw new InvalidArgumentException('employee.consent_required');
        }

        $employee = $this->employees->create(array_merge($data, [
            'company_id' => (int) config('app.company_id'),
            'created_by' => Auth::id(),
            'consent_by' => Auth::id(),
            'status' => Employee::DRAFT,
        ]));

        NotifyApproverJob::dispatch((int) $employee->getKey());

        return $employee;
    }
}
