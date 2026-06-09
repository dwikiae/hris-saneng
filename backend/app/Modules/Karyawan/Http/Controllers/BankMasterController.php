<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\BankMasterService;
use App\Modules\Karyawan\Http\Requests\StoreBankRequest;
use App\Modules\Karyawan\Http\Requests\UpdateBankRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class BankMasterController extends EmployeeMasterController
{
    public function __construct(BankMasterService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreBankRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateBankRequest $request, int $id): JsonResponse
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
            'swift' => $record->getAttribute('swift'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.banks.'.$action;
    }
}
