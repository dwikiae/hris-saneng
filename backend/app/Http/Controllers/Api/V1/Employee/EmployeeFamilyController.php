<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeFamilyRequest;
use App\Http\Requests\Employee\UpdateEmployeeFamilyRequest;
use App\Http\Resources\EmployeeFamilyResource;
use App\Services\Employee\EmployeeFamilyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeFamilyController extends Controller
{
    public function __construct(private readonly EmployeeFamilyService $family) {}

    public function index(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $family = $this->family->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeFamilyResource::collection($family)->resolve($request), 'employee.family.list');
    }

    public function store(StoreEmployeeFamilyRequest $request, int $id): JsonResponse
    {
        try {
            $family = $this->family->store($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeFamilyResource::make($family)->resolve($request), 'employee.family.created', 201);
    }

    public function update(UpdateEmployeeFamilyRequest $request, int $id, int $familyId): JsonResponse
    {
        try {
            $family = $this->family->update($id, $familyId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeFamilyResource::make($family)->resolve($request), 'employee.family.updated');
    }

    public function archive(int $id, int $familyId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $this->family->archive($id, $familyId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.family.archived');
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
