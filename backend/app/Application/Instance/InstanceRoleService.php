<?php

namespace App\Application\Instance;

use App\Models\Role;
use App\Repositories\Contracts\InstanceRoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InstanceRoleService
{
    public function __construct(private readonly InstanceRoleRepositoryInterface $roles) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->roles->paginate($filters, $perPage);
    }

    public function find(int $id): ?Role
    {
        return $this->roles->find($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Role
    {
        $role = $this->roles->create([
            'company_id' => $payload['company_id'],
            'code' => $this->code((string) $payload['name']),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        if (($payload['copy_from_role_id'] ?? null) !== null) {
            $template = $this->roles->find((int) $payload['copy_from_role_id']);

            if ($template instanceof Role) {
                $this->syncPermissions($role, $template->permissions->pluck('id')->map(fn ($id): int => (int) $id)->all());
            }
        }

        activity()
            ->useLog('roles')
            ->performedOn($role)
            ->event('created')
            ->log('instance.role.created');

        return $this->roles->find((int) $role->getKey()) ?? $role;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): ?Role
    {
        $role = $this->roles->find($id);

        if (! $role instanceof Role) {
            return null;
        }

        $updated = $this->roles->update($role, [
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'updated_by' => Auth::id(),
        ]);
        activity()
            ->useLog('roles')
            ->performedOn($updated)
            ->event('updated')
            ->log('instance.role.updated');

        return $updated;
    }

    public function archive(int $id, int $actorId): bool
    {
        $role = $this->roles->find($id);

        if (! $role instanceof Role) {
            return false;
        }

        $this->roles->archive($role, $actorId);
        activity()
            ->useLog('roles')
            ->performedOn($role)
            ->event('archived')
            ->log('instance.role.archived');

        return true;
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role|int $role, array $permissionIds): ?Role
    {
        $record = $role instanceof Role ? $role : $this->roles->find($role);

        if (! $record instanceof Role) {
            return null;
        }

        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $validPermissions = $this->roles->permissionsForCompany((int) $record->getAttribute('company_id'), $permissionIds);

        if ($validPermissions->count() !== count($permissionIds)) {
            throw new InvalidArgumentException('instance.permission.not_found');
        }

        $updated = $this->roles->syncPermissions($record, $permissionIds);
        activity()
            ->useLog('roles')
            ->performedOn($updated)
            ->event('permissions_synced')
            ->log('instance.role.permissions_synced');

        return $updated;
    }

    private function code(string $name): string
    {
        return Str::slug($name, '_').'_'.Str::lower(Str::random(6));
    }
}
