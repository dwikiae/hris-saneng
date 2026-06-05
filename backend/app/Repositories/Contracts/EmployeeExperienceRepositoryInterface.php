<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeExperience;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeExperienceRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeExperience>
     */
    public function listForEmployee(Employee $employee): Collection;

    public function findForEmployee(Employee $employee, int $experienceId): EmployeeExperience;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeExperience;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeExperience $experience, array $data): EmployeeExperience;

    public function archive(EmployeeExperience $experience): void;
}
