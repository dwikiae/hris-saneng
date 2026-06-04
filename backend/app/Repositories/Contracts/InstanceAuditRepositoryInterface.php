<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

interface InstanceAuditRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Activity>
     */
    public function allForExport(array $filters): Collection;
}
