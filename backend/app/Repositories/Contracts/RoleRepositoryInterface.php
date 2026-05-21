<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RoleRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function index(array $filters = []): Collection;

    public function show(int $id): ?Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model;

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(int $id, array $permissionIds): Model;
}
