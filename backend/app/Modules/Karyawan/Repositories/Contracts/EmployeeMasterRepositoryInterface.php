<?php

namespace App\Modules\Karyawan\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface EmployeeMasterRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function all(int $companyId, array $filters = []): Collection;

    public function find(int $companyId, int $id, bool $withArchived = false): ?Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $companyId, int $id, array $data): Model;

    public function archive(int $companyId, int $id, int $actorId): bool;

    public function restore(int $companyId, int $id): bool;
}
