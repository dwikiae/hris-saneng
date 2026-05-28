<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('user.view');

        $filters = [];
        $actor = $request->user();

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        if ($actor instanceof User && ! $actor->isInstanceAdmin()) {
            $filters['company_id'] = $actor->company_id;
        } elseif ($request->filled('company_id')) {
            $filters['company_id'] = $request->integer('company_id');
        }

        return $this->success(
            UserResource::collection($this->repository->all($filters))->resolve($request),
            'user.list'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        Gate::authorize('user.view');

        $record = $this->repository->findById($id);

        if ($record === null || ! $this->canAccessUser($request, $record)) {
            return $this->notFound();
        }

        return $this->success(UserResource::make($record)->resolve($request), 'user.detail');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User || $actor->isInstanceAdmin()) {
            return response()->json(['success' => false, 'message' => 'user.company_required'], 422);
        }

        $data = array_merge($request->validated(), [
            'company_id' => $actor->company_id,
            'password' => Hash::make($request->validated()['password']),
            'force_password_reset' => true,
            'created_by' => Auth::id(),
        ]);

        $record = $this->repository->create($data);

        return $this->success(UserResource::make($record)->resolve($request), 'user.created', 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null || ! $this->canAccessUser($request, $record)) {
                return $this->notFound();
            }

            $data = array_merge($request->validated(), ['updated_by' => Auth::id()]);
            $record = $this->repository->update($id, $data);
        } catch (ModelNotFoundException) {
            return $this->notFound();
        }

        return $this->success(UserResource::make($record)->resolve($request), 'user.updated');
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        Gate::authorize('user.archive');

        $record = $this->repository->findById($id);

        if ($record === null || ! $this->canAccessUser($request, $record)) {
            return $this->notFound();
        }

        if (! $this->repository->archive($id, (int) Auth::id())) {
            return $this->notFound();
        }

        return $this->success(null, 'user.archived');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        Gate::authorize('user.archive');

        $record = User::query()->withoutGlobalScope('not_archived')->find($id);

        if ($record === null || ! $this->canAccessUser($request, $record)) {
            return $this->notFound();
        }

        if (! $this->repository->restore($id)) {
            return $this->notFound();
        }

        return $this->success(null, 'user.restored');
    }

    public function syncRoles(Request $request, int $id): JsonResponse
    {
        Gate::authorize('user.assign_role');

        $record = $this->repository->findById($id);

        if ($record === null || ! $this->canAccessUser($request, $record)) {
            return $this->notFound();
        }

        $companyId = (int) $record->getAttribute('company_id');

        $validated = $request->validate([
            'role_ids' => ['required', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('company_id', $companyId),
            ],
        ]);

        try {
            $updated = $this->repository->syncRolesForCompany($id, $validated['role_ids'], $companyId);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'user.cross_company_forbidden'], 403);
        }

        return $this->success(UserResource::make($updated)->resolve($request), 'user.roles_synced');
    }

    private function canAccessUser(Request $request, mixed $record): bool
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $record instanceof User) {
            return false;
        }

        return $actor->isInstanceAdmin() || (int) $actor->company_id === (int) $record->company_id;
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'user.not_found'], 404);
    }
}
