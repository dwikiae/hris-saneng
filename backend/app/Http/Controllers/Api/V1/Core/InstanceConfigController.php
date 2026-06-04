<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\InstanceConfigService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\TestInstanceConfigSmtpRequest;
use App\Http\Requests\Instance\UpdateInstanceConfigRequest;
use App\Http\Resources\InstanceConfigResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InstanceConfigController extends Controller
{
    public function __construct(private readonly InstanceConfigService $config) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request);

        return $this->success(
            InstanceConfigResource::make($this->config->current())->resolve($request),
            'instance.config.detail'
        );
    }

    public function update(UpdateInstanceConfigRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            InstanceConfigResource::make(
                $this->config->update($request->validated(), $user)
            )->resolve($request),
            'instance.config.updated'
        );
    }

    public function testSmtp(TestInstanceConfigSmtpRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->config->dispatchTestSmtp($user);
        } catch (RuntimeException) {
            return $this->error('instance.config.smtp_incomplete', 422);
        }

        return $this->success(null, 'instance.config.test_smtp_queued');
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless($request->user()?->isInstanceAdmin(), 403);
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

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'meta' => [],
        ], $status);
    }
}
