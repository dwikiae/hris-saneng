<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeContractRequest;
use App\Http\Requests\Employee\UpdateEmployeeContractRequest;
use App\Http\Resources\EmployeeContractResource;
use App\Services\Employee\EmployeeContractService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class EmployeeContractController extends Controller
{
    public function __construct(private readonly EmployeeContractService $contracts) {}

    public function index(Request $request, int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $contracts = $this->contracts->list($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeContractResource::collection($contracts)->resolve($request), 'employee.contracts.list');
    }

    public function store(StoreEmployeeContractRequest $request, int $id): JsonResponse
    {
        try {
            $contract = $this->contracts->store($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeContractResource::make($contract)->resolve($request), 'employee.contracts.created', 201);
    }

    public function show(Request $request, int $id, int $contractId): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $contract = $this->contracts->show($id, $contractId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeContractResource::make($contract)->resolve($request), 'employee.contracts.detail');
    }

    public function update(UpdateEmployeeContractRequest $request, int $id, int $contractId): JsonResponse
    {
        try {
            $contract = $this->contracts->update($id, $contractId, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeContractResource::make($contract)->resolve($request), 'employee.contracts.updated');
    }

    public function approve(Request $request, int $id, int $contractId): JsonResponse
    {
        Gate::authorize('employee.approve');

        try {
            $contract = $this->contracts->approve($id, $contractId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeContractResource::make($contract)->resolve($request), 'employee.contracts.approved');
    }

    public function archive(int $id, int $contractId): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $this->contracts->archive($id, $contractId);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(null, 'employee.contracts.archived');
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
