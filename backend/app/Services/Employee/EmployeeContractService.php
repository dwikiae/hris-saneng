<?php

namespace App\Services\Employee;

use App\Models\EmployeeContract;
use App\Repositories\Contracts\EmployeeContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeContractService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeContractRepositoryInterface $contracts,
        private readonly EmployeeNoteService $notes
    ) {}

    /**
     * @return Collection<int, EmployeeContract>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->contracts->listForEmployee($employee);
    }

    public function show(int $employeeId, int $contractId): EmployeeContract
    {
        $employee = $this->employees->show($employeeId);

        return $this->contracts->findForEmployee($employee, $contractId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(int $employeeId, array $data): EmployeeContract
    {
        $employee = $this->employees->show($employeeId);
        $payload = $this->contractPayload($data);

        $payload['status'] = EmployeeContract::STATUS_DRAFT;
        $payload['created_by'] = Auth::id();
        $payload['updated_by'] = Auth::id();

        $this->validateContractDates($payload);

        return $this->contracts->createForEmployee($employee, $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $contractId, array $data): EmployeeContract
    {
        $employee = $this->employees->show($employeeId);
        $contract = $this->contracts->findForEmployee($employee, $contractId);
        $payload = $this->contractPayload($data);
        $merged = array_merge($contract->only(['contract_type', 'end_date']), $payload);

        $payload['updated_by'] = Auth::id();
        $this->validateContractDates($merged);

        return $this->contracts->update($contract, $payload);
    }

    public function approve(int $employeeId, int $contractId): EmployeeContract
    {
        return DB::transaction(function () use ($employeeId, $contractId): EmployeeContract {
            $employee = $this->employees->show($employeeId);
            $contract = $this->contracts->findForEmployee($employee, $contractId);

            $this->validateContractDates($contract->only(['contract_type', 'end_date']));

            foreach ($this->contracts->activeForEmployee($employee, (int) $contract->getKey()) as $activeContract) {
                $superseded = $this->contracts->update($activeContract, [
                    'status' => EmployeeContract::STATUS_SUPERSEDED,
                    'updated_by' => Auth::id(),
                ]);
                $this->contracts->archive($superseded);
                $this->notes->storeSystemForEmployee($employee, 'Kontrak '.$this->contractLabel($superseded).' digantikan');
            }

            $approved = $this->contracts->update($contract, [
                'status' => EmployeeContract::STATUS_ACTIVE,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->notes->storeSystemForEmployee($employee, 'Kontrak '.$this->contractLabel($approved).' diaktifkan');

            return $approved;
        });
    }

    public function archive(int $employeeId, int $contractId): void
    {
        $employee = $this->employees->show($employeeId);
        $contract = $this->contracts->findForEmployee($employee, $contractId);

        $this->contracts->archive($contract);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contractPayload(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'contract_type',
            'contract_number',
            'start_date',
            'end_date',
            'notes',
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateContractDates(array $data): void
    {
        if (($data['contract_type'] ?? null) !== EmployeeContract::TYPE_PKWT) {
            return;
        }

        if (! array_key_exists('end_date', $data) || $data['end_date'] === null || $data['end_date'] === '') {
            throw new InvalidArgumentException('employee.contracts.pkwt_end_date_required');
        }
    }

    private function contractLabel(EmployeeContract $contract): string
    {
        $number = $contract->getAttribute('contract_number');

        return is_string($number) && $number !== ''
            ? $number
            : '#'.$contract->getKey();
    }
}
