<?php

namespace App\Services\Employee;

use App\Models\EmployeeFamily;
use App\Repositories\Contracts\EmployeeFamilyRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeeFamilyService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeFamilyRepositoryInterface $family
    ) {}

    /**
     * @return Collection<int, EmployeeFamily>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->family->listForEmployee($employee);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(int $employeeId, array $data): EmployeeFamily
    {
        $employee = $this->employees->show($employeeId);

        return $this->family->createForEmployee($employee, array_merge($data, [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $familyId, array $data): EmployeeFamily
    {
        $employee = $this->employees->show($employeeId);
        $family = $this->family->findForEmployee($employee, $familyId);

        return $this->family->update($family, array_merge($data, [
            'updated_by' => Auth::id(),
        ]));
    }

    public function archive(int $employeeId, int $familyId): void
    {
        $employee = $this->employees->show($employeeId);
        $family = $this->family->findForEmployee($employee, $familyId);

        $this->family->archive($family);
    }
}
