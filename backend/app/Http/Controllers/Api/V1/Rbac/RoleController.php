<?php

namespace App\Http\Controllers\Api\V1\Rbac;

use App\Core\Company\Application\CompanyContext;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\RoleRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepository $repository,
        private readonly CompanyContext $companyContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        return $this->success($this->repository->index($filters), 'role.list');
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->repository->show($id);

        if ($record === null) {
            return $this->notFound();
        }

        return $this->success($record, 'role.detail');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('roles', 'code')
                    ->where('company_id', $this->companyId()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $data = array_merge($validated, [
            'company_id' => $this->companyId(),
            'created_by' => Auth::id(),
        ]);

        return $this->success($this->repository->create($data), 'role.created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        try {
            $record = $this->repository->update($id, array_merge($validated, ['updated_by' => Auth::id()]));
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($record, 'role.updated');
    }

    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id')
                    ->where('company_id', $this->companyId()),
            ],
        ]);

        try {
            $record = $this->repository->syncPermissions($id, $validated['permission_ids']);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success($record, 'role.permissions_synced');
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'role.not_found'], 404);
    }

    private function companyId(): int
    {
        return $this->companyContext->companyId();
    }
}
