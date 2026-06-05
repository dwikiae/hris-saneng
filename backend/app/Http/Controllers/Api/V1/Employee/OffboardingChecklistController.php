<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreOffboardingChecklistRequest;
use App\Http\Requests\Employee\UpdateOffboardingChecklistRequest;
use App\Http\Resources\OffboardingChecklistItemResource;
use App\Services\Employee\OffboardingChecklistService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OffboardingChecklistController extends Controller
{
    public function __construct(private readonly OffboardingChecklistService $checklist) {}

    public function index(Request $request, int $id, int $offId): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $items = $this->checklist->list($id, $offId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(OffboardingChecklistItemResource::collection($items)->resolve($request), 'employee.offboarding.checklist.list');
    }

    public function store(StoreOffboardingChecklistRequest $request, int $id, int $offId): JsonResponse
    {
        try {
            $item = $this->checklist->store($id, $offId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(OffboardingChecklistItemResource::make($item)->resolve($request), 'employee.offboarding.checklist.created', 201);
    }

    public function update(UpdateOffboardingChecklistRequest $request, int $id, int $offId, int $itemId): JsonResponse
    {
        try {
            $item = $this->checklist->update($id, $offId, $itemId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(OffboardingChecklistItemResource::make($item)->resolve($request), 'employee.offboarding.checklist.updated');
    }

    public function complete(Request $request, int $id, int $offId, int $itemId): JsonResponse
    {
        Gate::authorize('employee.update');

        try {
            $item = $this->checklist->complete($id, $offId, $itemId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(OffboardingChecklistItemResource::make($item)->resolve($request), 'employee.offboarding.checklist.completed');
    }

    public function archive(int $id, int $offId, int $itemId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $this->checklist->archive($id, $offId, $itemId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.offboarding.checklist.archived');
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
