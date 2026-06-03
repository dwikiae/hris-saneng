<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\InstanceUserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InstanceUserRepository implements InstanceUserRepositoryInterface
{
    public function __construct(private readonly User $model) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->with(['company', 'roles.company', 'invitations' => fn ($query) => $query->latest()])
            ->orderBy('name');

        if (($filters['search'] ?? '') !== '') {
            $search = (string) $filters['search'];
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%'));
        }

        if (($filters['status'] ?? '') === 'active') {
            $query->where('force_password_reset', false);
        } elseif (($filters['status'] ?? '') === 'pending') {
            $query->where('force_password_reset', true);
        }

        if (($filters['company_id'] ?? '') !== '') {
            $companyId = (int) $filters['company_id'];
            $query->where(fn ($query) => $query
                ->where('company_id', $companyId)
                ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('company_id', $companyId)));
        }

        if (($filters['role_id'] ?? '') !== '') {
            $roleId = (int) $filters['role_id'];
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->whereKey($roleId));
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->with(['company', 'roles.company', 'invitations' => fn ($query) => $query->latest()])
            ->find($id);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('email', $email)
            ->first();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        /** @var User $user */
        $user = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->create($data);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds): User
    {
        $user->roles()->sync($roleIds);

        return $this->find((int) $user->getKey()) ?? $user->load('roles.company');
    }

    /**
     * @param  array<int, int>  $roleIds
     * @return Collection<int, Role>
     */
    public function rolesByIds(array $roleIds): Collection
    {
        /** @var Collection<int, Role> $roles */
        $roles = Role::withoutCompanyScope()
            ->whereIn('id', $roleIds)
            ->with('company')
            ->get();

        return $roles;
    }

    public function archive(User $user, int $actorId): void
    {
        $user->archive($actorId);
    }
}
