<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeEducationRequest;
use App\Http\Requests\Employee\UpdateEmployeeEducationRequest;
use App\Http\Resources\EmployeeEducationResource;
use App\Services\Employee\EmployeeEducationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeEducationController extends Controller
{
    public function __construct(private readonly EmployeeEducationService $education) {}

    public function index(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $education = $this->education->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeEducationResource::collection($education)->resolve($request), 'employee.education.list');
    }

    public function store(StoreEmployeeEducationRequest $request, int $id): JsonResponse
    {
        try {
            $education = $this->education->store($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeEducationResource::make($education)->resolve($request), 'employee.education.created', 201);
    }

    public function update(UpdateEmployeeEducationRequest $request, int $id, int $eduId): JsonResponse
    {
        try {
            $education = $this->education->update($id, $eduId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeEducationResource::make($education)->resolve($request), 'employee.education.updated');
    }

    public function archive(int $id, int $eduId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $this->education->archive($id, $eduId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.education.archived');
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
