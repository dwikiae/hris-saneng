<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface InstancePermissionRepositoryInterface
{
    /**
     * @return Collection<int, \App\Models\Permission>
     */
    public function activePermissions(?int $companyId = null): Collection;
}
