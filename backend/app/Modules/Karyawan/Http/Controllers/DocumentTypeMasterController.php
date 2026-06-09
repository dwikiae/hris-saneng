<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Modules\Karyawan\Application\DocumentTypeMasterService;
use App\Modules\Karyawan\Http\Requests\StoreDocumentTypeRequest;
use App\Modules\Karyawan\Http\Requests\UpdateDocumentTypeRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class DocumentTypeMasterController extends EmployeeMasterController
{
    public function __construct(DocumentTypeMasterService $service)
    {
        parent::__construct($service);
    }

    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        return $this->create($request);
    }

    public function update(UpdateDocumentTypeRequest $request, int $id): JsonResponse
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
            'isMandatory' => (bool) $record->getAttribute('is_mandatory'),
            'description' => $record->getAttribute('description'),
            'isActive' => (bool) $record->getAttribute('is_active'),
            'status' => $record->getAttribute('archived_at') === null ? 'active' : 'archived',
            'createdAt' => $record->getAttribute('created_at'),
            'updatedAt' => $record->getAttribute('updated_at'),
        ];
    }

    protected function messageKey(string $action): string
    {
        return 'karyawan.document_types.'.$action;
    }
}
