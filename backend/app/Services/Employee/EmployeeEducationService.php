<?php

namespace App\Services\Employee;

use App\Models\EmployeeEducation;
use App\Repositories\Contracts\EmployeeEducationRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeeEducationService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeEducationRepositoryInterface $education
    ) {}

    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->education->listForEmployee($employee);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(int $employeeId, array $data): EmployeeEducation
    {
        $employee = $this->employees->show($employeeId);

        return $this->education->createForEmployee($employee, array_merge($data, [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $educationId, array $data): EmployeeEducation
    {
        $employee = $this->employees->show($employeeId);
        $education = $this->education->findForEmployee($employee, $educationId);

        return $this->education->update($education, array_merge($data, [
            'updated_by' => Auth::id(),
        ]));
    }

    public function archive(int $employeeId, int $educationId): void
    {
        $employee = $this->employees->show($employeeId);
        $education = $this->education->findForEmployee($employee, $educationId);

        $this->education->archive($education);
    }
}
