<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $code
 * @property string $name
 * @property string $version
 * @property string|null $description
 * @property bool $is_mandatory
 * @property array<int, string>|null $dependencies
 * @property bool $is_installed
 * @property Carbon|null $installed_at
 * @property Carbon|null $uninstalled_at
 */
class ModuleRegistryEntry extends Model
{
    protected $table = 'module_registry';

    protected $fillable = [
        'code',
        'name',
        'version',
        'description',
        'is_mandatory',
        'dependencies',
        'is_installed',
        'installed_at',
        'uninstalled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'dependencies' => 'array',
            'is_installed' => 'boolean',
            'installed_at' => 'datetime',
            'uninstalled_at' => 'datetime',
        ];
    }
}
