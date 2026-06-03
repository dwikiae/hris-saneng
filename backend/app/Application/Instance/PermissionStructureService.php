<?php

namespace App\Application\Instance;

use App\Core\ModuleRegistry\Application\ModuleRegistry;
use App\Core\ModuleRegistry\Domain\ModuleDefinition;
use App\Models\Permission;
use App\Repositories\Contracts\InstancePermissionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionStructureService
{
    public function __construct(private readonly InstancePermissionRepositoryInterface $permissions) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function structure(?int $companyId = null): array
    {
        $permissions = $this->permissions->activePermissions($companyId);
        $modules = $this->manifestModules();
        $tree = [];

        foreach ($modules as $module) {
            $menus = $this->manifestMenus($module, $permissions);

            if ($menus !== []) {
                $tree[] = [
                    'id' => $module->code,
                    'label' => $module->name,
                    'menus' => $menus,
                ];
            }
        }

        foreach ($this->fallbackModules($permissions, array_column($tree, 'id')) as $module) {
            $tree[] = $module;
        }

        return $tree;
    }

    /**
     * @return Collection<int, ModuleDefinition>
     */
    private function manifestModules(): Collection
    {
        return (new ModuleRegistry(base_path('app/Modules')))->all()
            ->filter(fn (ModuleDefinition $module): bool => $module->installed)
            ->values();
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return array<int, array<string, mixed>>
     */
    private function manifestMenus(ModuleDefinition $module, Collection $permissions): array
    {
        $manifest = $this->manifest($module);
        $menus = $manifest['permission_menus'] ?? null;

        if (! is_array($menus)) {
            return [];
        }

        return collect($menus)
            ->filter(fn (mixed $menu): bool => is_array($menu))
            ->map(fn (array $menu): array => [
                'id' => (string) ($menu['id'] ?? Str::slug((string) ($menu['label'] ?? $module->code))),
                'label' => (string) ($menu['label'] ?? $module->name),
                'actions' => $this->actions($permissions, (array) ($menu['actions'] ?? [])),
            ])
            ->filter(fn (array $menu): bool => $menu['actions'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @param  array<int, mixed>  $actionCodes
     * @return array<int, array<string, mixed>>
     */
    private function actions(Collection $permissions, array $actionCodes): array
    {
        return $permissions
            ->filter(fn (Permission $permission): bool => in_array($permission->code, $actionCodes, true))
            ->map(fn (Permission $permission): array => [
                'id' => $permission->getAttribute('id'),
                'code' => $permission->getAttribute('code'),
                'label' => $permission->getAttribute('name'),
                'action' => $permission->getAttribute('action'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @param  array<int, string>  $existingModuleIds
     * @return array<int, array<string, mixed>>
     */
    private function fallbackModules(Collection $permissions, array $existingModuleIds): array
    {
        return $permissions
            ->groupBy('module')
            ->reject(fn (Collection $items, string $module): bool => in_array($module, $existingModuleIds, true))
            ->map(fn (Collection $items, string $module): array => [
                'id' => $module,
                'label' => Str::headline($module),
                'menus' => [
                    [
                        'id' => $module,
                        'label' => Str::headline($module),
                        'actions' => $items->map(fn (Permission $permission): array => [
                            'id' => $permission->getAttribute('id'),
                            'code' => $permission->getAttribute('code'),
                            'label' => $permission->getAttribute('name'),
                            'action' => $permission->getAttribute('action'),
                        ])->values()->all(),
                    ],
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(ModuleDefinition $module): array
    {
        $path = $module->basePath.DIRECTORY_SEPARATOR.'module.json';
        $manifest = json_decode((string) file_get_contents($path), true);

        return is_array($manifest) ? $manifest : [];
    }
}
