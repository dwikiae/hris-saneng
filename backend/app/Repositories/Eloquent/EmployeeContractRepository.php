<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Repositories\Contracts\EmployeeContractRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;

class EmployeeContractRepository implements EmployeeContractRepositoryInterface
{
    public function __construct(private readonly ArchiveService $archiveService) {}

    /**
     * @return Collection<int, EmployeeContract>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return $employee->contracts()
            ->with('approver')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    public function findForEmployee(Employee $employee, int $contractId): EmployeeContract
    {
        /** @var EmployeeContract $contract */
        $contract = $employee->contracts()
            ->with('approver')
            ->findOrFail($contractId);

        return $contract;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeContract
    {
        /** @var EmployeeContract $contract */
        $contract = $employee->contracts()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $contract->load('approver');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeContract $contract, array $data): EmployeeContract
    {
        $contract->update($data);

        return $contract->refresh()->load('approver');
    }

    public function latestDraftForEmployee(Employee $employee): ?EmployeeContract
    {
        /** @var EmployeeContract|null $contract */
        $contract = $employee->contracts()
            ->where('status', EmployeeContract::STATUS_DRAFT)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $contract;
    }

    /**
     * @return Collection<int, EmployeeContract>
     */
    public function activeForEmployee(Employee $employee, ?int $exceptId = null): Collection
    {
        $query = $employee->contracts()
            ->where('status', EmployeeContract::STATUS_ACTIVE);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->get();
    }

    public function archive(EmployeeContract $contract): void
    {
        $this->archiveService->archive($contract);
    }
}
