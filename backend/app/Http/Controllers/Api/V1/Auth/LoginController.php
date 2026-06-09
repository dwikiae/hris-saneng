<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\UserPreferencesService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdateUserPreferencesRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    private const PLATFORM_SETTINGS_PERMISSION = 'platform.settings';

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->withoutGlobalScope('company')
            ->with(['roles' => fn ($q) => $q->withoutGlobalScope('company')->with('company')])
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'login.failed'], 401);
        }

        if ($user->isLocked()) {
            return response()->json(['success' => false, 'message' => 'login.locked'], 423);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            $attempts = $user->login_attempts + 1;
            $data = ['login_attempts' => $attempts];

            if ($attempts >= 5) {
                $data['locked_until'] = now()->addMinutes(15);
            }

            $user->update($data);

            return response()->json(['success' => false, 'message' => 'login.failed'], 401);
        }

        $user->update([
            'login_attempts' => 0,
            'last_login_at' => now(),
            'locked_until' => null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Preload permissions from already-loaded roles (avoids N+1)
        $roleIds = $user->roles->pluck('id');

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    ...$this->userPayload($user, $roleIds),
                ],
            ],
            'message' => 'login.success',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();
        $token->forceFill(['expires_at' => now()->subSecond()])->save();

        return response()->json(['success' => true, 'data' => null, 'message' => 'logout.success']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'auth.password.current_incorrect',
            ], 422);
        }

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $user->update([
            'password' => Hash::make($data['new_password']),
            'force_password_reset' => false,
        ]);

        $user->tokens()
            ->where('id', '!=', $currentToken->getKey())
            ->update(['expires_at' => now()->subSecond()]);

        return response()->json(['success' => true, 'data' => null, 'message' => 'auth.password.changed']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing([
            'roles' => fn ($q) => $q->withoutGlobalScope('company')->with('company'),
        ]);

        $roleIds = $user->roles->pluck('id');

        return response()->json([
            'success' => true,
            'data' => $this->userPayload($user, $roleIds),
            'message' => 'auth.me',
            'meta' => [],
        ]);
    }

    public function updatePreferences(
        UpdateUserPreferencesRequest $request,
        UserPreferencesService $userPreferences
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $updatedUser = $userPreferences->updateLanguagePreference(
            $user,
            (string) $request->validated('language_preference')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'language_preference' => $updatedUser->language_preference,
            ],
            'message' => 'user.preferences.updated',
        ]);
    }

    /**
     * @param  Collection<int, int>  $roleIds
     * @return array<int, string>
     */
    private function permissionCodes(User $user, Collection $roleIds): array
    {
        /** @var Collection<int, string> $codes */
        $codes = Permission::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
            ->pluck('code');

        if ($user->isInstanceAdmin() && ! $codes->contains(self::PLATFORM_SETTINGS_PERMISSION)) {
            $codes->push(self::PLATFORM_SETTINGS_PERMISSION);
        }

        return $codes->values()->all();
    }

    /**
     * @param  Collection<int, int>  $roleIds
     * @return array<string, mixed>
     */
    private function userPayload(User $user, Collection $roleIds): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'language_preference' => $user->language_preference,
            'force_password_reset' => $user->force_password_reset,
            'permissions' => $this->permissionCodes($user, $roleIds),
            'roles' => $this->rolePayload($user),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rolePayload(User $user): array
    {
        return $user->roles
            ->map(fn ($role): array => [
                'id' => $role->getAttribute('id'),
                'code' => $role->getAttribute('code'),
                'name' => $role->getAttribute('name'),
                'company_id' => $role->getAttribute('company_id'),
                'company_name' => $role->relationLoaded('company') && $role->getRelation('company') !== null
                    ? $role->getRelation('company')->getAttribute('name')
                    : null,
            ])
            ->values()
            ->all();
    }
}
