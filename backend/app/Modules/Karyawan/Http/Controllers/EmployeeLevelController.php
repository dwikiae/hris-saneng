<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\EmployeeLevelService;
use App\Modules\Karyawan\Http\Requests\StoreEmployeeLevelRequest;
use App\Modules\Karyawan\Http\Requests\UpdateEmployeeLevelRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class EmployeeLevelController extends EmployeeMasterController
{
    public function __construct(EmployeeLevelService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreEmployeeLevelRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateEmployeeLevelRequest $request, int $id): JsonResponse
    {
        return $this->replace($request, $id);
    }

    public function postUpdate(UpdateEmployeeLevelRequest $request, int $id): JsonResponse
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
            'description' => $record->getAttribute('description'),
            'order' => (int) $record->getAttribute('order'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.employee_levels.'.$action;
    }
}
