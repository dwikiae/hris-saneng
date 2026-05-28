<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserRepository
{
    public function __construct(private readonly User $model) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->with('roles');

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (array_key_exists('company_id', $filters)) {
            $query->withoutGlobalScope('company')
                ->where('company_id', $filters['company_id']);
        }

        /** @var Collection<int, Model> $results */
        $results = $query->get();

        return $results;
    }

    public function findById(int $id): ?Model
    {
        /** @var User|null $record */
        $record = $this->model->newQuery()->with('roles')->where('id', $id)->first();

        return $record;
    }

    public function findByEmail(string $email): ?Model
    {
        /** @var User|null $record */
        $record = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('email', $email)
            ->first();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        /** @var User $record */
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
            throw (new ModelNotFoundException)->setModel(User::class, $id);
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
        /** @var User|null $record */
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

    public function assignRole(int $id, int $roleId): Model
    {
        $record = $this->findById($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(User::class, $id);
        }

        /** @var User $record */
        $record->roles()->syncWithoutDetaching([$roleId]);

        return $record->load('roles');
    }

    public function removeRole(int $id, int $roleId): Model
    {
        $record = $this->findById($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(User::class, $id);
        }

        /** @var User $record */
        $record->roles()->detach($roleId);

        return $record->load('roles');
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(int $id, array $roleIds): Model
    {
        $record = $this->findById($id);

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel(User::class, $id);
        }

        /** @var User $record */
        $record->roles()->sync($roleIds);

        return $record->load('roles');
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRolesForCompany(int $id, array $roleIds, int $companyId): Model
    {
        $record = $this->findById($id);

        if ($record === null || (int) $record->getAttribute('company_id') !== $companyId) {
            throw (new ModelNotFoundException)->setModel(User::class, $id);
        }

        /** @var User $record */
        $validRoleIds = Role::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $roleIds)
            ->pluck('id')
            ->all();

        if (count($validRoleIds) !== count(array_unique($roleIds))) {
            throw new ModelNotFoundException;
        }

        $record->roles()->sync($validRoleIds);

        return $record->load('roles');
    }
}
