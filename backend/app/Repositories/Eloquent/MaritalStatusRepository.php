<?php

namespace App\Repositories\Eloquent;

use App\Models\MaritalStatus;
use App\Repositories\Contracts\MasterDataRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MaritalStatusRepository implements MasterDataRepositoryInterface
{
    public function __construct(private readonly MaritalStatus $model) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        /** @var Collection<int, Model> $results */
        $results = $query->get();

        return $results;
    }

    public function findById(int $id): ?Model
    {
        /** @var MaritalStatus|null $record */
        $record = $this->model->newQuery()->where('id', $id)->first();

        return $record;
    }

    public function findByCode(string $code): ?Model
    {
        /** @var MaritalStatus|null $record */
        $record = $this->model->newQuery()->where('code', $code)->first();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        /** @var MaritalStatus $record */
        $record = $this->model->newQuery()->create($data);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->findById($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(MaritalStatus::class, $id);
        }

        $record->update($data);

        return $record;
    }

    public function archive(int $id, int $archivedBy): bool
    {
        $record = $this->findById($id);

        if ($record === null) {
            return false;
        }

        $record->update(['archived_at' => now(), 'archived_by' => $archivedBy]);

        return true;
    }

    public function restore(int $id): bool
    {
        /** @var MaritalStatus|null $record */
        $record = $this->model->newQuery()
            ->withoutGlobalScope('not_archived')
            ->where('id', $id)
            ->first();

        if ($record === null) {
            return false;
        }

        $record->update(['archived_at' => null, 'archived_by' => null]);

        return true;
    }
}
