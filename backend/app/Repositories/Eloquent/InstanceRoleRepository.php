<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Models\Role;
use App\Repositories\Contracts\InstanceRoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InstanceRoleRepository implements InstanceRoleRepositoryInterface
{
    public function __construct(private readonly Role $model) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->with('company')
            ->withCount(['users', 'permissions'])
            ->orderBy('name');

        if (($filters['search'] ?? '') !== '') {
            $search = (string) $filters['search'];
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%'));
        }

        if (($filters['company_id'] ?? '') !== '') {
            $query->where('company_id', (int) $filters['company_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Role
    {
        /** @var Role|null $role */
        $role = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->with(['company', 'users.roles.company', 'permissions'])
            ->withCount(['users', 'permissions'])
            ->find($id);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        /** @var Role $role */
        $role = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->create($data);

        return $this->find((int) $role->getKey()) ?? $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $this->find((int) $role->getKey()) ?? $role->refresh();
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $role->permissions()->sync($permissionIds);

        return $this->find((int) $role->getKey()) ?? $role->load('permissions');
    }

    /**
     * @param  array<int, int>  $permissionIds
     * @return Collection<int, Permission>
     */
    public function permissionsForCompany(int $companyId, array $permissionIds): Collection
    {
        /** @var Collection<int, Permission> $permissions */
        $permissions = Permission::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->whereIn('id', $permissionIds)
            ->get();

        return $permissions;
    }

    public function archive(Role $role, int $actorId): void
    {
        $role->archive($actorId);
    }
}
