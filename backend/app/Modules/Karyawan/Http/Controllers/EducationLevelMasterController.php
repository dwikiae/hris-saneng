<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\EducationLevelMasterService;
use App\Modules\Karyawan\Http\Requests\StoreEducationLevelMasterRequest;
use App\Modules\Karyawan\Http\Requests\UpdateEducationLevelMasterRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class EducationLevelMasterController extends EmployeeMasterController
{
    public function __construct(EducationLevelMasterService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreEducationLevelMasterRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateEducationLevelMasterRequest $request, int $id): JsonResponse
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
            'order' => (int) $record->getAttribute('order'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.education_levels.'.$action;
    }
}
