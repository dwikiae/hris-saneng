<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\InstanceUserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\ArchiveInstanceUserRequest;
use App\Http\Requests\Instance\ListInstanceUsersRequest;
use App\Http\Requests\Instance\ResendInstanceUserInvitationRequest;
use App\Http\Requests\Instance\ShowInstanceUserRequest;
use App\Http\Requests\Instance\StoreInstanceUserRequest;
use App\Http\Requests\Instance\UpdateInstanceUserRequest;
use App\Http\Resources\InstanceUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class InstanceUserController extends Controller
{
    public function __construct(private readonly InstanceUserService $users) {}

    public function index(ListInstanceUsersRequest $request): JsonResponse
    {
        $paginator = $this->users->list($request->validated(), $request->integer('per_page', 20));
        $collection = $paginator->getCollection();
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'active' => $collection->filter(fn (User $user): bool => ! (bool) $user->getAttribute('force_password_reset'))->count(),
            'pending_invitation' => $collection->filter(fn (User $user): bool => (bool) $user->getAttribute('force_password_reset'))->count(),
        ];

        return $this->success([
            'items' => InstanceUserResource::collection($collection)->resolve($request),
            'meta' => $meta,
        ], 'instance.user.list', 200, $meta);
    }

    public function store(StoreInstanceUserRequest $request): JsonResponse
    {
        try {
            $user = $this->users->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(InstanceUserResource::make($user)->resolve($request), 'instance.user.created', 201);
    }

    public function show(ShowInstanceUserRequest $request, int $user): JsonResponse
    {
        $record = $this->users->find($user);

        if (! $record instanceof User) {
            return $this->notFound();
        }

        return $this->success(InstanceUserResource::make($record)->resolve($request), 'instance.user.detail');
    }

    public function update(UpdateInstanceUserRequest $request, int $user): JsonResponse
    {
        try {
            $record = $this->users->update($user, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (! $record instanceof User) {
            return $this->notFound();
        }

        return $this->success(InstanceUserResource::make($record)->resolve($request), 'instance.user.updated');
    }

    public function destroy(ArchiveInstanceUserRequest $request, int $user): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $this->users->archive($user, (int) $actor->getKey())) {
            return $this->notFound();
        }

        return $this->success(null, 'instance.user.archived');
    }

    public function resendInvitation(ResendInstanceUserInvitationRequest $request, int $user): JsonResponse
    {
        if (! $this->users->resendInvitation($user)) {
            return $this->notFound();
        }

        return $this->success(null, 'instance.user.invitation_resent');
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
        return $this->error('instance.user.not_found', 404);
    }
}
