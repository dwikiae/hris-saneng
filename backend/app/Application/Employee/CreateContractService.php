<?php

namespace App\Application\Employee;

use App\Domain\Employee\Exceptions\ContractConflictException;
use App\Domain\Employee\Exceptions\ContractOverlapException;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateContractService
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly EmployeeRepositoryInterface $employees
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ContractConflictException
     * @throws ContractOverlapException
     * @throws ValidationException
     */
    public function execute(Employee $employee, array $data): Contract
    {
        Gate::authorize('contract.create');

        $contractType = $this->contracts->findTypeById((int) $data['contract_type_id']);
        $this->ensureEndDateIsValid($contractType, $data);

        try {
            return DB::transaction(function () use ($employee, $data): Contract {
                $payload = $this->contractPayload($employee, $data);
                $active = $this->contracts->findActiveByEmployee((int) $employee->getKey());

                if ($active !== null && $payload['status'] === Contract::ACTIVE) {
                    $this->ensureDoesNotOverlapActiveContract($active, (string) $payload['start_date']);
                    $this->contracts->update($active, [
                        'status' => Contract::EXPIRED,
                        'updated_by' => Auth::id(),
                    ]);
                }

                $contract = $this->contracts->create($payload);
                $this->employees->createSystemLog($employee, 'Kontrak '.$contract->getAttribute('contract_number').' dibuat oleh '.$this->actorName());

                return $contract;
            });
        } catch (QueryException $exception) {
            throw new ContractConflictException('employee.contract.conflict', 0, $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function ensureEndDateIsValid(ContractType $contractType, array $data): void
    {
        if ((bool) $contractType->getAttribute('requires_end_date') && empty($data['end_date'])) {
            throw ValidationException::withMessages(['end_date' => ['employee.validation.contract_end_date_required']]);
        }
    }

    /**
     * @throws ContractOverlapException
     */
    private function ensureDoesNotOverlapActiveContract(Contract $activeContract, string $startDate): void
    {
        $activeEndDate = $activeContract->getAttribute('end_date');

        if ($activeEndDate === null) {
            throw new ContractOverlapException('employee.validation.contract_overlap');
        }

        if (CarbonImmutable::parse($startDate)->lessThanOrEqualTo($activeEndDate)) {
            throw new ContractOverlapException('employee.validation.contract_overlap');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contractPayload(Employee $employee, array $data): array
    {
        $companyId = (int) $employee->getAttribute('company_id');

        return array_merge($data, [
            'company_id' => $companyId,
            'employee_id' => $employee->getKey(),
            'contract_number' => $data['contract_number'] ?? $this->contracts->generateContractNumber($companyId),
            'status' => $data['status'] ?? Contract::DRAFT,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'System';
    }
}
