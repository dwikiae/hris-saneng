<?php

use App\Support\Modules\ModuleDefinition;
use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

uses(TestCase::class);

function mandatoryModuleRegistry(): ModuleRegistry
{
    return new ModuleRegistry(base_path('app/Modules'));
}

it('registers karyawan and kalender as mandatory modules', function () {
    $modules = mandatoryModuleRegistry()->all();

    expect($modules->pluck('code')->all())->toContain('kalender', 'karyawan');

    foreach (['karyawan', 'kalender'] as $code) {
        $module = mandatoryModuleRegistry()->find($code);
        $modulePath = realpath($module->basePath);
        $migrationPath = realpath($module->migrationsFullPath());

        expect($module)
            ->toBeInstanceOf(ModuleDefinition::class)
            ->and($module->isMandatory)->toBeTrue()
            ->and($module->installed)->toBeTrue()
            ->and($module->uninstallable)->toBeFalse()
            ->and($module->toggleable)->toBeFalse()
            ->and($module->canBeUninstalled())->toBeFalse()
            ->and($module->canBeToggled())->toBeFalse()
            ->and($modulePath)->not->toBeFalse()
            ->and($migrationPath)->not->toBeFalse()
            ->and(str_starts_with((string) $migrationPath, (string) $modulePath))->toBeTrue()
            ->and($module->translations)->toContain('id', 'en');
    }
});
