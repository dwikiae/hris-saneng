<?php

namespace App\Support\Modules;

use Illuminate\Support\Collection;
use InvalidArgumentException;

final readonly class ModuleRegistry
{
    public function __construct(private string $modulesPath) {}

    /**
     * @return Collection<int, ModuleDefinition>
     */
    public function all(): Collection
    {
        if (! is_dir($this->modulesPath)) {
            return collect();
        }

        $modules = [];

        foreach (scandir($this->modulesPath) ?: [] as $directory) {
            if ($directory === '.' || $directory === '..') {
                continue;
            }

            $modulePath = $this->modulesPath.DIRECTORY_SEPARATOR.$directory;

            if (! is_dir($modulePath)) {
                continue;
            }

            $modules[] = $this->load($modulePath);
        }

        return collect($modules)->sortBy('code')->values();
    }

    public function find(string $code): ModuleDefinition
    {
        $module = $this->all()->first(fn (ModuleDefinition $definition): bool => $definition->code === $code);

        if (! $module instanceof ModuleDefinition) {
            throw new InvalidArgumentException('module.not_found');
        }

        return $module;
    }

    private function load(string $modulePath): ModuleDefinition
    {
        $manifestPath = $modulePath.DIRECTORY_SEPARATOR.'module.json';

        if (! is_file($manifestPath)) {
            throw new InvalidArgumentException('module.manifest_missing');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest)) {
            throw new InvalidArgumentException('module.manifest_invalid_json');
        }

        return ModuleDefinition::fromManifest($manifest, $modulePath);
    }
}
