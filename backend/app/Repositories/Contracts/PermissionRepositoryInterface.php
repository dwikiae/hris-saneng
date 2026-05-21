<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PermissionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function index(array $filters = []): Collection;

    public function show(int $id): ?Model;
}
