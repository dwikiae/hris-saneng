<?php

namespace App\Application\Employee;

use App\Models\Contract;
use App\Models\User;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TerminateContractService
{
    public function __construct(
        private readonly ContractRepositoryInterface $contracts,
        private readonly EmployeeRepositoryInterface $employees
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(Contract $contract, array $data): Contract
    {
        Gate::authorize('contract.terminate');

        if ($contract->getAttribute('status') !== Contract::ACTIVE) {
            throw ValidationException::withMessages(['status' => ['employee.validation.contract_not_active']]);
        }

        $updated = $this->contracts->update($contract, [
            'status' => Contract::TERMINATED,
            'termination_reason' => $data['termination_reason'] ?? null,
            'termination_date' => $data['termination_date'] ?? now()->toDateString(),
            'termination_notes' => $data['termination_notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $employee = $this->employees->findById((int) $updated->getAttribute('employee_id'));
        $this->employees->createSystemLog($employee, 'Kontrak '.$updated->getAttribute('contract_number').' diterminasi oleh '.$this->actorName());

        return $updated;
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'System';
    }
}
