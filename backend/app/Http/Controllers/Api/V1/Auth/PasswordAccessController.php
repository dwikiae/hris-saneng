<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\PasswordAccessService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\SetPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordAccessController extends Controller
{
    public function __construct(private readonly PasswordAccessService $passwords) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwords->requestReset((string) $request->validated('email'));

        return $this->success(null, 'auth.password_reset.requested');
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $valid = $this->passwords->resetPassword(
            (string) $request->validated('email'),
            (string) $request->validated('token'),
            (string) $request->validated('password')
        );

        if (! $valid) {
            return $this->error('auth.password_reset.invalid_token', 422);
        }

        return $this->success(null, 'auth.password_reset.completed');
    }

    public function set(SetPasswordRequest $request): JsonResponse
    {
        $valid = $this->passwords->setPassword(
            (string) $request->validated('token'),
            (string) $request->validated('password')
        );

        if (! $valid) {
            return $this->error('auth.invitation.invalid_token', 422);
        }

        return $this->success(null, 'auth.invitation.accepted');
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
