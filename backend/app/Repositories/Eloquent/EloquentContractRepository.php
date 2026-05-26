<?php

namespace App\Repositories\Eloquent;

use App\Models\Contract;
use App\Models\ContractType;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentContractRepository implements ContractRepositoryInterface
{
    public function __construct(private readonly Contract $model) {}

    public function findById(int $id): Contract
    {
        /** @var Contract $contract */
        $contract = $this->model->newQuery()
            ->with(['contractType', 'position', 'department', 'workLocation'])
            ->findOrFail($id);

        return $contract;
    }

    public function findTypeById(int $id): ContractType
    {
        /** @var ContractType $contractType */
        $contractType = ContractType::query()->findOrFail($id);

        return $contractType;
    }

    public function listByEmployee(int $employeeId): Collection
    {
        /** @var Collection<int, Contract> $contracts */
        $contracts = $this->model->newQuery()
            ->with(['contractType', 'position', 'department', 'workLocation'])
            ->where('employee_id', $employeeId)
            ->orderByDesc('start_date')
            ->get();

        return $contracts;
    }

    public function findActiveByEmployee(int $employeeId): ?Contract
    {
        /** @var Contract|null $contract */
        $contract = $this->model->newQuery()
            ->with(['contractType', 'position', 'department', 'workLocation'])
            ->where('employee_id', $employeeId)
            ->where('status', Contract::ACTIVE)
            ->first();

        return $contract;
    }

    public function listActiveExpiringOnDates(array $dates): Collection
    {
        /** @var Collection<int, Contract> $contracts */
        $contracts = $this->model->newQuery()
            ->with(['employee', 'contractType'])
            ->where('status', Contract::ACTIVE)
            ->whereNotNull('end_date')
            ->where(function ($query) use ($dates): void {
                foreach ($dates as $date) {
                    $query->orWhereDate('end_date', $date);
                }
            })
            ->orderBy('end_date')
            ->get();

        return $contracts;
    }

    public function create(array $data): Contract
    {
        /** @var Contract $contract */
        $contract = $this->model->newQuery()->create($data);

        return $contract->refresh();
    }

    public function update(Contract $contract, array $data): Contract
    {
        $contract->update($data);

        return $contract->refresh();
    }

    public function generateContractNumber(int $companyId): string
    {
        return DB::transaction(function () use ($companyId): string {
            $year = date('Y');
            $last = $this->model->newQuery()
                ->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('contract_number', 'like', "CTR-{$year}-%")
                ->lockForUpdate()
                ->max('contract_number');

            $next = is_string($last) ? ((int) substr($last, -4)) + 1 : 1;

            return 'CTR-'.$year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }

    public function terminateActiveByEmployee(int $employeeId, string $terminationDate, int $userId): int
    {
        return $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->where('status', Contract::ACTIVE)
            ->update([
                'status' => Contract::TERMINATED,
                'termination_date' => $terminationDate,
                'termination_reason' => 'employee_offboarded',
                'updated_by' => $userId,
            ]);
    }
}
