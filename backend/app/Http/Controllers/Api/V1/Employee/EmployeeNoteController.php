<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeNoteRequest;
use App\Http\Resources\EmployeeNoteResource;
use App\Services\Employee\EmployeeNoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeNoteController extends Controller
{
    public function __construct(private readonly EmployeeNoteService $notes) {}

    public function index(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $notes = $this->notes->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeNoteResource::collection($notes)->resolve($request), 'employee.notes.list');
    }

    public function store(StoreEmployeeNoteRequest $request, int $id): JsonResponse
    {
        try {
            $note = $this->notes->storeManual($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeNoteResource::make($note)->resolve($request), 'employee.notes.created', 201);
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
