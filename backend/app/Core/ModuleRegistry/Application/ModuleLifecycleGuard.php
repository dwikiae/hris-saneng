<?php

namespace App\Core\ModuleRegistry\Application;

use App\Core\ModuleRegistry\Domain\ModuleDefinition;
use InvalidArgumentException;

final readonly class ModuleLifecycleGuard
{
    public function __construct(private ModuleRegistry $registry) {}

    /**
     * @param  array<int, string>  $installedModuleCodes
     */
    public function assertCanInstall(string $code, array $installedModuleCodes): ModuleDefinition
    {
        $module = $this->registry->find($code);

        if ($module->isMandatory) {
            throw new InvalidArgumentException('module.lifecycle.mandatory_locked');
        }

        if (in_array($module->code, $installedModuleCodes, true)) {
            throw new InvalidArgumentException('module.lifecycle.already_installed');
        }

        $this->assertDependenciesSatisfied($module, $installedModuleCodes);

        return $module;
    }

    /**
     * @param  array<int, string>  $installedModuleCodes
     * @param  array<int, string>  $enabledModuleCodes
     */
    public function assertCanEnable(
        string $code,
        array $installedModuleCodes,
        array $enabledModuleCodes,
    ): ModuleDefinition {
        $module = $this->registry->find($code);

        if ($module->isMandatory || ! $module->canBeToggled()) {
            throw new InvalidArgumentException('module.lifecycle.mandatory_locked');
        }

        if (! in_array($module->code, $installedModuleCodes, true)) {
            throw new InvalidArgumentException('module.lifecycle.not_installed');
        }

        $this->assertDependenciesSatisfied($module, $enabledModuleCodes);

        return $module;
    }

    public function assertCanDisable(string $code): ModuleDefinition
    {
        $module = $this->registry->find($code);

        if ($module->isMandatory || ! $module->canBeToggled()) {
            throw new InvalidArgumentException('module.lifecycle.mandatory_locked');
        }

        return $module;
    }

    public function assertCanUninstall(string $code, bool $exportGenerated, bool $confirmed): ModuleDefinition
    {
        $module = $this->registry->find($code);

        if ($module->isMandatory || ! $module->canBeUninstalled()) {
            throw new InvalidArgumentException('module.lifecycle.mandatory_locked');
        }

        if (! $exportGenerated) {
            throw new InvalidArgumentException('module.lifecycle.export_required');
        }

        if (! $confirmed) {
            throw new InvalidArgumentException('module.lifecycle.confirmation_required');
        }

        return $module;
    }

    /**
     * @param  array<int, string>  $availableModuleCodes
     */
    private function assertDependenciesSatisfied(ModuleDefinition $module, array $availableModuleCodes): void
    {
        $missingDependencies = array_values(array_diff($module->dependencies, $availableModuleCodes));

        if ($missingDependencies !== []) {
            throw new InvalidArgumentException('module.lifecycle.dependencies_required');
        }
    }
}
