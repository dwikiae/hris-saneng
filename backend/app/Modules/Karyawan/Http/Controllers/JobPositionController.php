<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\JobPositionService;
use App\Modules\Karyawan\Http\Requests\StoreJobPositionRequest;
use App\Modules\Karyawan\Http\Requests\UpdateJobPositionRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class JobPositionController extends EmployeeMasterController
{
    public function __construct(JobPositionService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreJobPositionRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateJobPositionRequest $request, int $id): JsonResponse
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
            'departmentId' => $record->getAttribute('department_id'),
            'description' => $record->getAttribute('description'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.job_positions.'.$action;
    }
}
