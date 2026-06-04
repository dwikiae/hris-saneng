<?php

namespace App\Repositories\Contracts;

use App\Models\ModuleRegistryEntry;
use Illuminate\Support\Collection;

interface InstanceModuleRepositoryInterface
{
    /**
     * @return Collection<string, ModuleRegistryEntry>
     */
    public function allByCode(): Collection;

    public function findByCode(string $code): ?ModuleRegistryEntry;

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(string $code, array $data): ModuleRegistryEntry;
}
