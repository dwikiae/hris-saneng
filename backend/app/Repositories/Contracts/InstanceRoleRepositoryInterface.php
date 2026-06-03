<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InstanceRoleRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role;

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): Role;

    /**
     * @param  array<int, int>  $permissionIds
     * @return Collection<int, \App\Models\Permission>
     */
    public function permissionsForCompany(int $companyId, array $permissionIds): Collection;

    public function archive(Role $role, int $actorId): void;
}
