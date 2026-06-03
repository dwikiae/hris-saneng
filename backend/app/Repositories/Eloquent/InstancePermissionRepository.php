<?php

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Repositories\Contracts\InstancePermissionRepositoryInterface;
use Illuminate\Support\Collection;

class InstancePermissionRepository implements InstancePermissionRepositoryInterface
{
    /**
     * @return Collection<int, Permission>
     */
    public function activePermissions(?int $companyId = null): Collection
    {
        /** @var Collection<int, Permission> $permissions */
        $permissions = Permission::withoutCompanyScope()
            ->where('is_active', true)
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('module')
            ->orderBy('action')
            ->get();

        return $permissions;
    }
}
