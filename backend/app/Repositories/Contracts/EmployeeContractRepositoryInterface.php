<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeContractRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeContract>
     */
    public function listForEmployee(Employee $employee): Collection;

    public function findForEmployee(Employee $employee, int $contractId): EmployeeContract;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeContract;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeContract $contract, array $data): EmployeeContract;

    /**
     * @return Collection<int, EmployeeContract>
     */
    public function activeForEmployee(Employee $employee, ?int $exceptId = null): Collection;

    public function archive(EmployeeContract $contract): void;
}
