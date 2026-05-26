<?php

namespace App\Application\Employee;

use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateContractService
{
    public function __construct(private readonly ContractRepositoryInterface $contracts) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(Contract $contract, array $data): Contract
    {
        Gate::authorize('contract.create');

        if ($contract->getAttribute('status') === Contract::ACTIVE) {
            throw new AuthorizationException('employee.validation.contract_active_locked');
        }

        if (array_key_exists('contract_type_id', $data)) {
            $contractType = $this->contracts->findTypeById((int) $data['contract_type_id']);

            if ((bool) $contractType->getAttribute('requires_end_date') && empty($data['end_date'])) {
                throw ValidationException::withMessages(['end_date' => ['employee.validation.contract_end_date_required']]);
            }
        }

        return $this->contracts->update($contract, array_merge($data, [
            'updated_by' => Auth::id(),
        ]));
    }
}
