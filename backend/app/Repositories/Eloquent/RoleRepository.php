<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(private readonly Role $model) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function index(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        /** @var Collection<int, Model> $results */
        $results = $query->get();

        return $results;
    }

    public function show(int $id): ?Model
    {
        /** @var Role|null $record */
        $record = $this->model->newQuery()->where('id', $id)->first();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        /** @var Role $record */
        $record = $this->model->newQuery()->create($data);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->show($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(Role::class, $id);
        }

        $record->update($data);

        return $record;
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(int $id, array $permissionIds): Model
    {
        $record = $this->show($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(Role::class, $id);
        }

        /** @var Role $record */
        $record->permissions()->sync($permissionIds);

        return $record->load('permissions');
    }
}
