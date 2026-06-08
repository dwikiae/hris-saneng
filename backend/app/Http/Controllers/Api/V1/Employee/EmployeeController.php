<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ArchiveService;
use App\Services\Employee\ApproveEmployeeService;
use App\Services\Employee\CreateEmployeeService;
use App\Services\Employee\UpdateEmployeeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly CreateEmployeeService $creator,
        private readonly UpdateEmployeeService $updater,
        private readonly ApproveEmployeeService $approver,
        private readonly ArchiveService $archiveService
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('employee.view');

        $filters = $request->only(['status', 'department_id', 'position_id', 'search']);
        $perPage = max(1, min($request->integer('per_page', 20), 100));

        $employees = $this->employees->index($filters, $perPage)
            ->through(fn ($employee): array => EmployeeResource::make($employee)->resolve($request));

        return $this->success($employees, 'employee.list');
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $employee = $this->creator->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeResource::make($employee)->resolve($request), 'employee.created', 201);
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize('employee.view');

        try {
            $employee = $this->employees->show($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(EmployeeResource::make($employee)->resolve(request()), 'employee.detail');
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        try {
            $employee = $this->employees->show($id);
            $updated = $this->updater->update($employee, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeResource::make($updated)->resolve($request), 'employee.updated');
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $employee = $this->employees->show($id);
            $updated = $this->approver->submit($employee);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeResource::make($updated)->resolve(request()), 'employee.submitted');
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $employee = $this->employees->show($id);
            $updated = $this->approver->approve($employee);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeResource::make($updated)->resolve(request()), 'employee.approved');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $employee = $this->employees->show($id);
            $updated = $this->approver->reject($employee, $request->string('reason')->toString());
        } catch (ModelNotFoundException) {
            return $this->notFound();
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(EmployeeResource::make($updated)->resolve($request), 'employee.rejected');
    }

    public function archive(int $id): JsonResponse
    {
        Gate::authorize('employee.archive');

        try {
            $employee = $this->employees->show($id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        $this->archiveService->archive($employee);

        return $this->success(null, 'employee.archived');
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
