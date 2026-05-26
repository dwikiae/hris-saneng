<?php

namespace App\Repositories\Contracts;

use App\Models\Contract;
use App\Models\ContractType;
use Illuminate\Database\Eloquent\Collection;

interface ContractRepositoryInterface
{
    public function findById(int $id): Contract;

    public function findTypeById(int $id): ContractType;

    /**
     * @return Collection<int, Contract>
     */
    public function listByEmployee(int $employeeId): Collection;

    public function findActiveByEmployee(int $employeeId): ?Contract;

    /**
     * @param  list<string>  $dates
     * @return Collection<int, Contract>
     */
    public function listActiveExpiringOnDates(array $dates): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Contract;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contract $contract, array $data): Contract;

    public function generateContractNumber(int $companyId): string;

    public function terminateActiveByEmployee(int $employeeId, string $terminationDate, int $userId): int;
}
