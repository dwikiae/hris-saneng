<?php

namespace App\Application\Employee;

use App\Models\Contract;
use App\Models\Employee;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RenewContractService
{
    public function __construct(
        private readonly CreateContractService $createContractService,
        private readonly ContractRepositoryInterface $contracts
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(Employee $employee, Contract $activeContract, array $data): Contract
    {
        Gate::authorize('contract.create');
        $this->ensureCanRenew($activeContract, $data);

        return $this->createContractService->execute($employee, array_merge($data, [
            'contract_type_id' => $activeContract->getAttribute('contract_type_id'),
            'position_id' => $activeContract->getAttribute('position_id'),
            'department_id' => $activeContract->getAttribute('department_id'),
            'work_location_id' => $activeContract->getAttribute('work_location_id'),
            'start_date' => $data['start_date'] ?? CarbonImmutable::parse($activeContract->getAttribute('end_date'))->addDay()->toDateString(),
            'status' => Contract::ACTIVE,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function ensureCanRenew(Contract $activeContract, array $data): void
    {
        if ($activeContract->getAttribute('status') !== Contract::ACTIVE || $activeContract->getAttribute('end_date') === null) {
            throw ValidationException::withMessages(['contract' => ['employee.validation.contract_not_renewable']]);
        }

        $contractType = $this->contracts->findTypeById((int) $activeContract->getAttribute('contract_type_id'));

        if ($contractType->getAttribute('code') !== 'PKWT') {
            throw ValidationException::withMessages(['contract_type_id' => ['employee.validation.contract_not_pkwt']]);
        }

        if (empty($data['end_date'])) {
            throw ValidationException::withMessages(['end_date' => ['employee.validation.contract_end_date_required']]);
        }
    }
}
