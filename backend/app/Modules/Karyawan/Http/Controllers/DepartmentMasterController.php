<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\DepartmentMasterService;
use App\Modules\Karyawan\Http\Requests\StoreDepartmentRequest;
use App\Modules\Karyawan\Http\Requests\UpdateDepartmentRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class DepartmentMasterController extends EmployeeMasterController
{
    public function __construct(DepartmentMasterService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
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
            'description' => $record->getAttribute('description'),
            'parentId' => $record->getAttribute('parent_id'),
            'totalEmployees' => (int) $record->getAttribute('total_employees'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.departments.'.$action;
    }
}
