<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeOffboarding;

interface EmployeeOffboardingRepositoryInterface
{
    public function activeForEmployee(Employee $employee): ?EmployeeOffboarding;

    public function hasAnyHistoryForEmployee(Employee $employee): bool;

    public function findForEmployee(Employee $employee, int $offboardingId): EmployeeOffboarding;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeOffboarding;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeOffboarding $offboarding, array $data): EmployeeOffboarding;
}
