<?php

use App\Core\ModuleRegistry\Application\ModuleLifecycleGuard;
use App\Core\ModuleRegistry\Application\ModuleRegistry;
use Tests\TestCase;

uses(TestCase::class);

function moduleLifecycleGuard(): ModuleLifecycleGuard
{
    return new ModuleLifecycleGuard(new ModuleRegistry(base_path('app/Modules')));
}

it('blocks installing mandatory modules', function () {
    moduleLifecycleGuard()->assertCanInstall('karyawan', []);
})->throws(InvalidArgumentException::class, 'module.lifecycle.mandatory_locked');

it('blocks installing already installed optional modules', function () {
    moduleLifecycleGuard()->assertCanInstall('website', ['website']);
})->throws(InvalidArgumentException::class, 'module.lifecycle.already_installed');

it('requires optional module dependencies before install', function () {
    moduleLifecycleGuard()->assertCanInstall('recruitment', []);
})->throws(InvalidArgumentException::class, 'module.lifecycle.dependencies_required');

it('allows installing optional modules when dependencies are installed', function () {
    $module = moduleLifecycleGuard()->assertCanInstall('recruitment', ['karyawan']);

    expect($module->code)->toBe('recruitment');
});

it('blocks enabling mandatory modules', function () {
    moduleLifecycleGuard()->assertCanEnable('kalender', ['kalender'], ['kalender']);
})->throws(InvalidArgumentException::class, 'module.lifecycle.mandatory_locked');

it('blocks enabling modules that are not installed at instance level', function () {
    moduleLifecycleGuard()->assertCanEnable('website', [], []);
})->throws(InvalidArgumentException::class, 'module.lifecycle.not_installed');

it('requires optional module dependencies before enable', function () {
    moduleLifecycleGuard()->assertCanEnable('aset', ['aset', 'karyawan'], []);
})->throws(InvalidArgumentException::class, 'module.lifecycle.dependencies_required');

it('allows enabling optional modules when installed and dependencies are enabled', function () {
    $module = moduleLifecycleGuard()->assertCanEnable('aset', ['aset', 'karyawan'], ['karyawan']);

    expect($module->code)->toBe('aset');
});

it('blocks disabling mandatory modules', function () {
    moduleLifecycleGuard()->assertCanDisable('karyawan');
})->throws(InvalidArgumentException::class, 'module.lifecycle.mandatory_locked');

it('allows disabling optional toggleable modules', function () {
    $module = moduleLifecycleGuard()->assertCanDisable('website');

    expect($module->code)->toBe('website');
});

it('blocks uninstalling mandatory modules', function () {
    moduleLifecycleGuard()->assertCanUninstall('kalender', true, true);
})->throws(InvalidArgumentException::class, 'module.lifecycle.mandatory_locked');

it('requires export before uninstalling optional modules', function () {
    moduleLifecycleGuard()->assertCanUninstall('website', false, true);
})->throws(InvalidArgumentException::class, 'module.lifecycle.export_required');

it('requires confirmation before uninstalling optional modules', function () {
    moduleLifecycleGuard()->assertCanUninstall('website', true, false);
})->throws(InvalidArgumentException::class, 'module.lifecycle.confirmation_required');

it('allows uninstalling optional modules after export and confirmation', function () {
    $module = moduleLifecycleGuard()->assertCanUninstall('website', true, true);

    expect($module->code)->toBe('website');
});
