<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InstanceUserRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds): User;

    /**
     * @param  array<int, int>  $roleIds
     * @return Collection<int, \App\Models\Role>
     */
    public function rolesByIds(array $roleIds): Collection;

    public function archive(User $user, int $actorId): void;
}
