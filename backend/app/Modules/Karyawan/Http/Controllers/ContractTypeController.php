<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\ContractTypeService;
use App\Modules\Karyawan\Http\Requests\StoreContractTypeRequest;
use App\Modules\Karyawan\Http\Requests\UpdateContractTypeRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ContractTypeController extends EmployeeMasterController
{
    public function __construct(ContractTypeService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreContractTypeRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateContractTypeRequest $request, int $id): JsonResponse
    {
        return $this->replace($request, $id);
    }

    protected function resource(Model $record): array
    {
        return [
            'id' => $record->getKey(),
            'companyId' => $record->getAttribute('company_id'),
            'code' => $record->getAttribute('code'),
            'name' => $record->getAttribute('name'),
            'type' => $record->getAttribute('type'),
            'description' => $record->getAttribute('description'),
            'maxDurationMonths' => $record->getAttribute('max_duration_months'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.contract_types.'.$action;
    }
}
