<?php

namespace App\Modules\Karyawan\Application;

use App\Modules\Karyawan\Repositories\Contracts\EmployeeMasterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeMasterService
{
    public function __construct(private readonly EmployeeMasterRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function list(int $companyId, array $filters = []): Collection
    {
        return $this->repository->all($companyId, $filters);
    }

    public function find(int $companyId, int $id): Model
    {
        $record = $this->repository->find($companyId, $id);

        if (! $record instanceof Model) {
            throw new ModelNotFoundException;
        }

        return $record;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(int $companyId, array $payload, int $actorId): Model
    {
        return $this->repository->create($companyId, array_merge($payload, ['created_by' => $actorId]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $companyId, int $id, array $payload, int $actorId): Model
    {
        return $this->repository->update($companyId, $id, array_merge($payload, ['updated_by' => $actorId]));
    }

    public function archive(int $companyId, int $id, int $actorId): bool
    {
        return $this->repository->archive($companyId, $id, $actorId);
    }

    public function restore(int $companyId, int $id): bool
    {
        return $this->repository->restore($companyId, $id);
    }
}
