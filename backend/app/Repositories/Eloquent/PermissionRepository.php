<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(private readonly Permission $model) {}

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

        if (array_key_exists('module', $filters)) {
            $query->where('module', $filters['module']);
        }

        /** @var Collection<int, Model> $results */
        $results = $query->get();

        return $results;
    }

    public function show(int $id): ?Model
    {
        /** @var Permission|null $record */
        $record = $this->model->newQuery()->where('id', $id)->first();

        return $record;
    }
}
