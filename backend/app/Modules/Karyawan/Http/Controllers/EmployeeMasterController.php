<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Karyawan\Application\EmployeeMasterService;
use App\Modules\Karyawan\Http\Requests\ListEmployeeMasterRequest;
use App\Modules\Karyawan\Http\Requests\StoreEmployeeLevelRequest;
use App\Modules\Karyawan\Http\Requests\StoreWorkLocationRequest;
use App\Modules\Karyawan\Http\Requests\UpdateEmployeeLevelRequest;
use App\Modules\Karyawan\Http\Requests\UpdateWorkLocationRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

abstract class EmployeeMasterController extends Controller
{
    public function __construct(private readonly EmployeeMasterService $service) {}

    public function index(ListEmployeeMasterRequest $request): JsonResponse
    {
        $filters = [];
        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }
        if ($request->filled('search')) {
            $filters['search'] = (string) $request->query('search');
        }

        $items = $this->service->list($this->companyId($request), $filters)
            ->map(fn (Model $record): array => $this->resource($record))
            ->values();

        return $this->success($items, $this->messageKey('list'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        Gate::authorize('karyawan.settings');

        try {
            $record = $this->service->find($this->companyId($request), $id);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($this->resource($record), $this->messageKey('detail'));
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        Gate::authorize('karyawan.settings');

        if (! $this->service->archive($this->companyId($request), $id, (int) $request->user()?->getKey())) {
            return $this->notFound();
        }

        return $this->success(null, $this->messageKey('archived'));
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        Gate::authorize('karyawan.settings');

        if (! $this->service->restore($this->companyId($request), $id)) {
            return $this->notFound();
        }

        return $this->success(null, $this->messageKey('restored'));
    }

    protected function create(StoreWorkLocationRequest|StoreEmployeeLevelRequest $request): JsonResponse
    {
        $record = $this->service->create(
            $this->companyId($request),
            $request->validated(),
            (int) $request->user()?->getKey()
        );

        return $this->success($this->resource($record), $this->messageKey('created'), 201);
    }

    protected function replace(UpdateWorkLocationRequest|UpdateEmployeeLevelRequest $request, int $id): JsonResponse
    {
        try {
            $record = $this->service->update(
                $this->companyId($request),
                $id,
                $request->validated(),
                (int) $request->user()?->getKey()
            );
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($this->resource($record), $this->messageKey('updated'));
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function resource(Model $record): array;

    abstract protected function messageKey(string $action): string;

    private function companyId(Request $request): int
    {
        return (int) $request->attributes->get('company_id');
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => [],
        ], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $this->messageKey('not_found'),
            'meta' => [],
        ], 404);
    }
}
