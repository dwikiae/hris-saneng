<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeFamily;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeFamilyRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeFamily>
     */
    public function listForEmployee(Employee $employee): Collection;

    public function findForEmployee(Employee $employee, int $familyId): EmployeeFamily;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeFamily;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeFamily $family, array $data): EmployeeFamily;

    public function archive(EmployeeFamily $family): void;
}
