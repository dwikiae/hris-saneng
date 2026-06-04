<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\WorkLocationService;
use App\Modules\Karyawan\Http\Requests\StoreWorkLocationRequest;
use App\Modules\Karyawan\Http\Requests\UpdateWorkLocationRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class WorkLocationController extends EmployeeMasterController
{
    public function __construct(WorkLocationService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreWorkLocationRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateWorkLocationRequest $request, int $id): JsonResponse
    {
        return $this->replace($request, $id);
    }

    public function postUpdate(UpdateWorkLocationRequest $request, int $id): JsonResponse
    {
        return $this->replace($request, $id);
    }

    protected function resource(Model $record): array
    {
        return [
            'id' => $record->getAttribute('id'),
            'companyId' => $record->getAttribute('company_id'),
            'code' => $record->getAttribute('code'),
            'name' => $record->getAttribute('name'),
            'address' => $record->getAttribute('address'),
            'cityId' => $record->getAttribute('city_id'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.work_locations.'.$action;
    }
}
