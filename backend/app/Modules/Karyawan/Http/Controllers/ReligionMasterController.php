<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\ReligionMasterService;
use App\Modules\Karyawan\Http\Requests\StoreReligionRequest;
use App\Modules\Karyawan\Http\Requests\UpdateReligionRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class ReligionMasterController extends EmployeeMasterController
{
    public function __construct(ReligionMasterService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreReligionRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateReligionRequest $request, int $id): JsonResponse
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
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.religions.'.$action;
    }
}
