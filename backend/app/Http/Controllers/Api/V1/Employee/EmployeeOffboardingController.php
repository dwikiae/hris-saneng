<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Exceptions\IncompleteOffboardingChecklistException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeOffboardingRequest;
use App\Http\Requests\Employee\UpdateEmployeeOffboardingRequest;
use App\Http\Resources\EmployeeOffboardingResource;
use App\Services\Employee\EmployeeOffboardingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class EmployeeOffboardingController extends Controller
{
    public function __construct(private readonly EmployeeOffboardingService $offboardings) {}

    public function show(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $current = $this->offboardings->current($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success([
            'offboarding' => $current['offboarding'] === null
                ? null
                : EmployeeOffboardingResource::make($current['offboarding'])->resolve($request),
            'is_visible' => $current['is_visible'],
        ], 'employee.offboarding.detail');
    }

    public function store(StoreEmployeeOffboardingRequest $request, int $id): JsonResponse
    {
        try {
            $offboarding = $this->offboardings->initiate($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeOffboardingResource::make($offboarding)->resolve($request), 'employee.offboarding.created', 201);
    }

    public function update(UpdateEmployeeOffboardingRequest $request, int $id, int $offId): JsonResponse
    {
        try {
            $offboarding = $this->offboardings->update($id, $offId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeOffboardingResource::make($offboarding)->resolve($request), 'employee.offboarding.updated');
    }

    public function complete(Request $request, int $id, int $offId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $offboarding = $this->offboardings->complete($id, $offId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (IncompleteOffboardingChecklistException $exception) {
            return response()->json([
                'success' => false,
                'data' => ['incomplete_checklist_items' => $exception->items()],
                'message' => $exception->getMessage(),
            ], 422);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeOffboardingResource::make($offboarding)->resolve($request), 'employee.offboarding.completed');
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
