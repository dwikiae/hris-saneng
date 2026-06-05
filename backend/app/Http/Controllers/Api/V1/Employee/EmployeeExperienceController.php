<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeExperienceRequest;
use App\Http\Requests\Employee\UpdateEmployeeExperienceRequest;
use App\Http\Resources\EmployeeExperienceResource;
use App\Services\Employee\EmployeeExperienceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class EmployeeExperienceController extends Controller
{
    public function __construct(private readonly EmployeeExperienceService $experience) {}

    public function index(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $experience = $this->experience->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeExperienceResource::collection($experience)->resolve($request), 'employee.experience.list');
    }

    public function store(StoreEmployeeExperienceRequest $request, int $id): JsonResponse
    {
        try {
            $experience = $this->experience->store($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeExperienceResource::make($experience)->resolve($request), 'employee.experience.created', 201);
    }

    public function update(UpdateEmployeeExperienceRequest $request, int $id, int $expId): JsonResponse
    {
        try {
            $experience = $this->experience->update($id, $expId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeExperienceResource::make($experience)->resolve($request), 'employee.experience.updated');
    }

    public function archive(int $id, int $expId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $this->experience->archive($id, $expId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.experience.archived');
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
