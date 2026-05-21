<?php

namespace App\Application\MasterData;

use App\Models\EmploymentType;
use App\Repositories\Contracts\MasterDataRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmploymentTypeService
{
    public function __construct(private readonly MasterDataRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function list(array $filters = []): Collection
    {
        return $this->repository->all($filters);
    }

    public function findById(int $id): Model
    {
        $record = $this->repository->findById($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(EmploymentType::class, $id);
        }

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $createdBy): Model
    {
        return $this->repository->create(array_merge($data, ['created_by' => $createdBy]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, int $updatedBy): Model
    {
        return $this->repository->update($id, array_merge($data, ['updated_by' => $updatedBy]));
    }

    public function archive(int $id, int $archivedBy): bool
    {
        return $this->repository->archive($id, $archivedBy);
    }

    public function restore(int $id): bool
    {
        return $this->repository->restore($id);
    }
}
