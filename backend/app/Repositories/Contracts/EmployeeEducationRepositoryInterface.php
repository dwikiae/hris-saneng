<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeEducationRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function listForEmployee(Employee $employee): Collection;

    public function findForEmployee(Employee $employee, int $educationId): EmployeeEducation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeEducation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeEducation $education, array $data): EmployeeEducation;

    public function archive(EmployeeEducation $education): void;
}
