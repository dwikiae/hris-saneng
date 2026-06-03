<?php

namespace App\Application\Instance;

use App\Application\Auth\PasswordAccessService;
use App\Jobs\Auth\SendUserInvitationJob;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Repositories\Contracts\InstanceUserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InstanceUserService
{
    public function __construct(
        private readonly InstanceUserRepositoryInterface $users,
        private readonly PasswordAccessService $passwords,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->users->paginate($filters, $perPage);
    }

    public function find(int $id): ?User
    {
        return $this->users->find($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): User
    {
        $roleIds = $this->roleIds($payload);
        $companyIds = $this->companyIds($payload, $roleIds);
        $user = $this->users->create([
            'company_id' => $companyIds[0] ?? null,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'employee_id' => $payload['employee_id'] ?? null,
            'password' => Hash::make(Str::random(40)),
            'force_password_reset' => true,
            'created_by' => Auth::id(),
        ]);

        $user = $this->users->syncRoles($user, $roleIds);
        $this->createInvitation($user);
        activity()
            ->useLog('users')
            ->performedOn($user)
            ->event('created')
            ->log('instance.user.created');

        return $this->users->find((int) $user->getKey()) ?? $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): ?User
    {
        $user = $this->users->find($id);

        if (! $user instanceof User) {
            return null;
        }

        $data = [
            'name' => $payload['name'],
            'employee_id' => $payload['employee_id'] ?? null,
            'updated_by' => Auth::id(),
        ];

        if (array_key_exists('email', $payload)) {
            $data['email'] = $payload['email'];
        }

        $roleIds = $this->roleIds($payload);
        $companyIds = $this->companyIds($payload, $roleIds);
        $data['company_id'] = $companyIds[0] ?? null;

        $updated = $this->users->update($user, $data);
        $updated = $this->users->syncRoles($updated, $roleIds);
        activity()
            ->useLog('users')
            ->performedOn($updated)
            ->event('updated')
            ->log('instance.user.updated');

        return $updated;
    }

    public function archive(int $id, int $actorId): bool
    {
        $user = $this->users->find($id);

        if (! $user instanceof User) {
            return false;
        }

        $this->users->archive($user, $actorId);
        activity()
            ->useLog('users')
            ->performedOn($user)
            ->event('archived')
            ->log('instance.user.archived');

        return true;
    }

    public function resendInvitation(int $id): bool
    {
        $user = $this->users->find($id);

        if (! $user instanceof User) {
            return false;
        }

        $this->createInvitation($user);
        activity()
            ->useLog('users')
            ->performedOn($user)
            ->event('invitation_resent')
            ->log('instance.user.invitation_resent');

        return true;
    }

    private function createInvitation(User $user): void
    {
        $token = $this->passwords->newToken();

        UserInvitation::query()->create([
            'user_id' => $user->getKey(),
            'token_hash' => $this->passwords->hashToken($token),
            'expires_at' => now()->addHours(PasswordAccessService::INVITATION_TTL_HOURS),
            'created_by' => Auth::id(),
        ]);

        SendUserInvitationJob::dispatch((int) $user->getKey(), $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, int>
     */
    private function roleIds(array $payload): array
    {
        $roleIds = array_values(array_unique(array_map('intval', $payload['role_ids'] ?? [])));

        if ($roleIds === []) {
            return [];
        }

        $roles = $this->users->rolesByIds($roleIds);

        if ($roles->count() !== count($roleIds)) {
            throw new InvalidArgumentException('instance.role.not_found');
        }

        return $roleIds;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int>  $roleIds
     * @return array<int, int>
     */
    private function companyIds(array $payload, array $roleIds): array
    {
        $companyIds = array_values(array_unique(array_map('intval', $payload['company_ids'] ?? [])));

        if ($roleIds === []) {
            return $companyIds;
        }

        return $this->users->rolesByIds($roleIds)
            ->map(fn (Role $role): int => (int) $role->getAttribute('company_id'))
            ->merge($companyIds)
            ->unique()
            ->values()
            ->all();
    }
}
