<?php

namespace App\Support\Modules;

use InvalidArgumentException;

final readonly class ModuleDefinition
{
    /**
     * @param  array<int, string>  $translations
     * @param  array<int, string>  $dependencies
     */
    private function __construct(
        public string $code,
        public string $name,
        public string $description,
        public string $version,
        public string $namespace,
        public bool $isMandatory,
        public bool $installed,
        public bool $uninstallable,
        public bool $toggleable,
        public string $migrationsPath,
        public array $translations,
        public array $dependencies,
        public string $basePath,
    ) {}

    /**
     * @param  array<string, mixed>  $manifest
     */
    public static function fromManifest(array $manifest, string $basePath): self
    {
        foreach (self::requiredKeys() as $key) {
            if (! array_key_exists($key, $manifest)) {
                throw new InvalidArgumentException("module.manifest_missing_{$key}");
            }
        }

        $definition = new self(
            code: self::stringValue($manifest, 'code'),
            name: self::stringValue($manifest, 'name'),
            description: self::stringValue($manifest, 'description'),
            version: self::stringValue($manifest, 'version'),
            namespace: self::stringValue($manifest, 'namespace'),
            isMandatory: self::boolValue($manifest, 'is_mandatory'),
            installed: self::boolValue($manifest, 'installed'),
            uninstallable: self::boolValue($manifest, 'uninstallable'),
            toggleable: self::boolValue($manifest, 'toggleable'),
            migrationsPath: self::stringValue($manifest, 'migrations_path'),
            translations: self::translations($manifest),
            dependencies: self::dependencies($manifest),
            basePath: $basePath,
        );

        if ($definition->isMandatory && (! $definition->installed || $definition->uninstallable || $definition->toggleable)) {
            throw new InvalidArgumentException('module.mandatory_modules_are_locked');
        }

        return $definition;
    }

    public function migrationsFullPath(): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->migrationsPath);
    }

    public function canBeUninstalled(): bool
    {
        return ! $this->isMandatory && $this->uninstallable;
    }

    public function canBeToggled(): bool
    {
        return ! $this->isMandatory && $this->toggleable;
    }

    /**
     * @return array<int, string>
     */
    private static function requiredKeys(): array
    {
        return [
            'code',
            'name',
            'description',
            'version',
            'namespace',
            'is_mandatory',
            'installed',
            'uninstallable',
            'toggleable',
            'migrations_path',
            'translations',
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private static function stringValue(array $manifest, string $key): string
    {
        if (! is_string($manifest[$key]) || $manifest[$key] === '') {
            throw new InvalidArgumentException("module.manifest_invalid_{$key}");
        }

        return $manifest[$key];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private static function boolValue(array $manifest, string $key): bool
    {
        if (! is_bool($manifest[$key])) {
            throw new InvalidArgumentException("module.manifest_invalid_{$key}");
        }

        return $manifest[$key];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<int, string>
     */
    private static function translations(array $manifest): array
    {
        if (! is_array($manifest['translations'])) {
            throw new InvalidArgumentException('module.manifest_invalid_translations');
        }

        foreach ($manifest['translations'] as $translation) {
            if (! is_string($translation) || $translation === '') {
                throw new InvalidArgumentException('module.manifest_invalid_translations');
            }
        }

        return array_values($manifest['translations']);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<int, string>
     */
    private static function dependencies(array $manifest): array
    {
        if (! array_key_exists('dependencies', $manifest)) {
            return [];
        }

        if (! is_array($manifest['dependencies'])) {
            throw new InvalidArgumentException('module.manifest_invalid_dependencies');
        }

        foreach ($manifest['dependencies'] as $dependency) {
            if (! is_string($dependency) || $dependency === '') {
                throw new InvalidArgumentException('module.manifest_invalid_dependencies');
            }
        }

        return array_values($manifest['dependencies']);
    }
}
