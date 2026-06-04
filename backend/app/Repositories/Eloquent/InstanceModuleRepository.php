<?php

namespace App\Repositories\Eloquent;

use App\Models\ModuleRegistryEntry;
use App\Repositories\Contracts\InstanceModuleRepositoryInterface;
use Illuminate\Support\Collection;

class InstanceModuleRepository implements InstanceModuleRepositoryInterface
{
    public function __construct(private readonly ModuleRegistryEntry $model) {}

    /**
     * @return Collection<string, ModuleRegistryEntry>
     */
    public function allByCode(): Collection
    {
        /** @var Collection<string, ModuleRegistryEntry> $entries */
        $entries = $this->model->newQuery()
            ->get()
            ->keyBy('code');

        return $entries;
    }

    public function findByCode(string $code): ?ModuleRegistryEntry
    {
        /** @var ModuleRegistryEntry|null $entry */
        $entry = $this->model->newQuery()
            ->where('code', $code)
            ->first();

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(string $code, array $data): ModuleRegistryEntry
    {
        /** @var ModuleRegistryEntry $entry */
        $entry = $this->model->newQuery()->updateOrCreate(
            ['code' => $code],
            $data
        );

        return $entry->refresh();
    }
}
