<?php

use App\Support\Modules\ModuleDefinition;
use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

uses(TestCase::class);

function optionalModuleRegistry(): ModuleRegistry
{
    return new ModuleRegistry(base_path('app/Modules'));
}

it('registers recruitment aset and website as optional modules', function () {
    $expectations = [
        'recruitment' => ['karyawan'],
        'aset' => ['karyawan'],
        'website' => [],
    ];

    foreach ($expectations as $code => $dependencies) {
        $module = optionalModuleRegistry()->find($code);
        $modulePath = realpath($module->basePath);
        $migrationPath = realpath($module->migrationsFullPath());

        expect($module)
            ->toBeInstanceOf(ModuleDefinition::class)
            ->and($module->isMandatory)->toBeFalse()
            ->and($module->installed)->toBeFalse()
            ->and($module->uninstallable)->toBeTrue()
            ->and($module->toggleable)->toBeTrue()
            ->and($module->canBeUninstalled())->toBeTrue()
            ->and($module->canBeToggled())->toBeTrue()
            ->and($module->dependencies)->toBe($dependencies)
            ->and($modulePath)->not->toBeFalse()
            ->and($migrationPath)->not->toBeFalse()
            ->and(str_starts_with((string) $migrationPath, (string) $modulePath))->toBeTrue()
            ->and($module->translations)->toContain('id', 'en');
    }
});
