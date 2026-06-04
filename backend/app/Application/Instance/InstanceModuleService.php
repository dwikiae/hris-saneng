<?php

namespace App\Application\Instance;

use App\Core\ModuleRegistry\Application\ModuleRegistry;
use App\Core\ModuleRegistry\Domain\ModuleDefinition;
use App\Models\ModuleRegistryEntry;
use App\Models\User;
use App\Repositories\Contracts\InstanceModuleRepositoryInterface;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class InstanceModuleService
{
    private ModuleRegistry $manifestRegistry;

    public function __construct(
        private readonly InstanceModuleRepositoryInterface $modules,
        private readonly InstanceSettingsRepositoryInterface $settings,
        private readonly InstanceModuleMigrationRunner $migrations,
        private readonly InstanceModuleExportService $exports,
    ) {
        $this->manifestRegistry = new ModuleRegistry(base_path('app/Modules'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $entries = $this->modules->allByCode();
        $installed = $this->installedCodes($entries);

        return $this->manifestRegistry->all()
            ->map(fn (ModuleDefinition $module): array => $this->resource($module, $entries->get($module->code), $installed))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function install(string $code, User $actor): array
    {
        $module = $this->find($code);
        $entries = $this->modules->allByCode();

        if ($module->isMandatory) {
            throw new ModuleLifecycleException('module.lifecycle.mandatory_locked');
        }

        if (in_array($module->code, $this->installedCodes($entries), true)) {
            throw new ModuleLifecycleException('module.lifecycle.already_installed');
        }

        $missing = $this->missingDependencies($module, $this->installedCodes($entries));

        if ($missing !== []) {
            throw new ModuleLifecycleException('module.lifecycle.dependencies_required', ['missingDependencies' => $missing]);
        }

        $this->migrations->migrate($module);
        $entry = $this->modules->upsert($module->code, [
            ...$this->entryData($module),
            'is_installed' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ]);

        activity('instance')->causedBy($actor)->event('installed')->withProperties([
            'module' => $module->code,
            'version' => $module->version,
        ])->log('instance.module.installed');

        return $this->resource($module, $entry, $this->installedCodes($this->modules->allByCode()));
    }

    /**
     * @return array<string, mixed>
     */
    public function exportData(string $code, User $actor): array
    {
        $module = $this->find($code);
        $export = $this->exports->export($module);
        $markers = $this->exportMarkers();
        $markers[$module->code] = [
            'export_id' => $export['exportId'],
            'created_at' => now()->toISOString(),
        ];
        $this->settings->set('module_uninstall_exports', $markers);

        activity('instance')->causedBy($actor)->event('exported')->withProperties([
            'module' => $module->code,
            'export_id' => $export['exportId'],
            'record_count' => $export['recordCount'],
        ])->log('instance.module.exported');

        unset($export['recordCount']);

        return $export;
    }

    public function uninstall(string $code, User $actor): void
    {
        $module = $this->find($code);
        $entry = $this->modules->findByCode($module->code);

        if ($module->isMandatory || ! $module->canBeUninstalled()) {
            throw new ModuleLifecycleException('module.lifecycle.mandatory_locked');
        }

        if (! $entry instanceof ModuleRegistryEntry || ! $entry->is_installed) {
            throw new ModuleLifecycleException('module.lifecycle.not_installed');
        }

        $markers = $this->exportMarkers();

        if (! array_key_exists($module->code, $markers)) {
            throw new ModuleLifecycleException('module.lifecycle.export_required');
        }

        $this->migrations->rollback($module);
        $this->modules->upsert($module->code, [
            ...$this->entryData($module),
            'is_installed' => false,
            'installed_at' => null,
            'uninstalled_at' => now(),
        ]);
        unset($markers[$module->code]);
        $this->settings->set('module_uninstall_exports', $markers);

        activity('instance')->causedBy($actor)->event('uninstalled')->withProperties([
            'module' => $module->code,
            'warning' => 'UU PDP: export data generated before module uninstall',
        ])->log('instance.module.uninstalled');
    }

    private function find(string $code): ModuleDefinition
    {
        try {
            return $this->manifestRegistry->find($code);
        } catch (InvalidArgumentException $exception) {
            throw new ModuleLifecycleException($exception->getMessage(), [], 404);
        }
    }

    /**
     * @param  Collection<string, ModuleRegistryEntry>  $entries
     * @return array<int, string>
     */
    private function installedCodes(Collection $entries): array
    {
        return $this->manifestRegistry->all()
            ->filter(fn (ModuleDefinition $module): bool => $this->isInstalled($module, $entries->get($module->code)))
            ->pluck('code')
            ->values()
            ->all();
    }

    private function isInstalled(ModuleDefinition $module, ?ModuleRegistryEntry $entry): bool
    {
        return $module->isMandatory || ($entry?->is_installed ?? $module->installed);
    }

    /**
     * @param  array<int, string>  $installedCodes
     * @return array<int, string>
     */
    private function missingDependencies(ModuleDefinition $module, array $installedCodes): array
    {
        return array_values(array_diff($module->dependencies, $installedCodes));
    }

    /**
     * @param  array<int, string>  $installedCodes
     * @return array<string, mixed>
     */
    private function resource(ModuleDefinition $module, ?ModuleRegistryEntry $entry, array $installedCodes): array
    {
        $installed = $this->isInstalled($module, $entry);

        return [
            'code' => $module->code,
            'name' => $entry?->name ?? $module->name,
            'version' => $entry?->version ?? $module->version,
            'description' => $entry?->description ?? $module->description,
            'isMandatory' => $module->isMandatory,
            'dependencies' => $module->dependencies,
            'missingDependencies' => $this->missingDependencies($module, $installedCodes),
            'isInstalled' => $installed,
            'installedAt' => $entry?->installed_at?->toISOString(),
            'status' => $installed ? 'installed' : 'not_installed',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entryData(ModuleDefinition $module): array
    {
        return [
            'name' => $module->name,
            'version' => $module->version,
            'description' => $module->description,
            'is_mandatory' => $module->isMandatory,
            'dependencies' => $module->dependencies,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportMarkers(): array
    {
        $markers = $this->settings->get('module_uninstall_exports');

        return is_array($markers) ? $markers : [];
    }
}
