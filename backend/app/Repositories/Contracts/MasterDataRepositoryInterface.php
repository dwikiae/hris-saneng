<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface MasterDataRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function all(array $filters = []): Collection;

    public function findById(int $id): ?Model;

    public function findByCode(string $code): ?Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model;

    public function archive(int $id, int $archivedBy): bool;

    public function restore(int $id): bool;
}
