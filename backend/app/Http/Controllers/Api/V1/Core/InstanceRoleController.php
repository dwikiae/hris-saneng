<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\InstanceRoleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\ArchiveInstanceRoleRequest;
use App\Http\Requests\Instance\ListInstanceRolesRequest;
use App\Http\Requests\Instance\ShowInstanceRoleRequest;
use App\Http\Requests\Instance\StoreInstanceRoleRequest;
use App\Http\Requests\Instance\SyncInstanceRolePermissionsRequest;
use App\Http\Requests\Instance\UpdateInstanceRoleRequest;
use App\Http\Resources\InstanceRoleResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class InstanceRoleController extends Controller
{
    public function __construct(private readonly InstanceRoleService $roles) {}

    public function index(ListInstanceRolesRequest $request): JsonResponse
    {
        $paginator = $this->roles->list($request->validated(), $request->integer('per_page', 20));
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        return $this->success([
            'items' => InstanceRoleResource::collection($paginator->getCollection())->resolve($request),
            'meta' => $meta,
        ], 'instance.role.list', 200, $meta);
    }

    public function store(StoreInstanceRoleRequest $request): JsonResponse
    {
        $role = $this->roles->create($request->validated());

        return $this->success(InstanceRoleResource::make($role)->resolve($request), 'instance.role.created', 201);
    }

    public function show(ShowInstanceRoleRequest $request, int $role): JsonResponse
    {
        $record = $this->roles->find($role);

        if (! $record instanceof Role) {
            return $this->notFound();
        }

        return $this->success(InstanceRoleResource::make($record)->resolve($request), 'instance.role.detail');
    }

    public function update(UpdateInstanceRoleRequest $request, int $role): JsonResponse
    {
        $record = $this->roles->update($role, $request->validated());

        if (! $record instanceof Role) {
            return $this->notFound();
        }

        return $this->success(InstanceRoleResource::make($record)->resolve($request), 'instance.role.updated');
    }

    public function destroy(ArchiveInstanceRoleRequest $request, int $role): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $this->roles->archive($role, (int) $actor->getKey())) {
            return $this->notFound();
        }

        return $this->success(null, 'instance.role.archived');
    }

    public function permissions(SyncInstanceRolePermissionsRequest $request, int $role): JsonResponse
    {
        try {
            $record = $this->roles->syncPermissions($role, $request->validated('permission_ids'));
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (! $record instanceof Role) {
            return $this->notFound();
        }

        return $this->success(InstanceRoleResource::make($record)->resolve($request), 'instance.role.permissions_synced');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function success(mixed $data, string $message, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'meta' => [],
        ], $status);
    }

    private function notFound(): JsonResponse
    {
        return $this->error('instance.role.not_found', 404);
    }
}
