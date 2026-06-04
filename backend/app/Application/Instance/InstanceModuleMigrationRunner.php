<?php

namespace App\Application\Instance;

use App\Core\ModuleRegistry\Domain\ModuleDefinition;
use Illuminate\Support\Facades\Artisan;

class InstanceModuleMigrationRunner
{
    public function migrate(ModuleDefinition $module): void
    {
        if (! $this->hasMigrationFiles($module)) {
            return;
        }

        Artisan::call('migrate', [
            '--path' => $this->relativePath($module),
            '--force' => true,
        ]);
    }

    public function rollback(ModuleDefinition $module): void
    {
        if (! $this->hasMigrationFiles($module)) {
            return;
        }

        Artisan::call('migrate:rollback', [
            '--path' => $this->relativePath($module),
            '--force' => true,
        ]);
    }

    private function hasMigrationFiles(ModuleDefinition $module): bool
    {
        $path = $module->migrationsFullPath();

        if (! is_dir($path)) {
            return false;
        }

        return glob($path.DIRECTORY_SEPARATOR.'*.php') !== [];
    }

    private function relativePath(ModuleDefinition $module): string
    {
        return str_replace(
            ['\\', base_path().DIRECTORY_SEPARATOR],
            ['/', ''],
            $module->migrationsFullPath()
        );
    }
}
