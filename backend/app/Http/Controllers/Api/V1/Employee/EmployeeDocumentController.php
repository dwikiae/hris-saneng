<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeDocumentRequest;
use App\Services\Employee\EmployeeDocumentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class EmployeeDocumentController extends Controller
{
    public function __construct(private readonly EmployeeDocumentService $documents) {}

    public function index(int $id): JsonResponse
    {
        Gate::authorize('employee.update');

        try {
            $documents = $this->documents->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($documents, 'employee.documents.list');
    }

    public function store(StoreEmployeeDocumentRequest $request, int $id): JsonResponse
    {
        try {
            $document = $this->documents->store(
                $id,
                (string) $request->validated('doc_type'),
                $request->file('document')
            );
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success($document, 'employee.documents.uploaded', 201);
    }

    public function archive(int $id, int $docId): JsonResponse
    {
        Gate::authorize('employee.update');

        try {
            $this->documents->archive($id, $docId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.documents.archived');
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return $this->error('employee.not_found', 404);
    }
}
