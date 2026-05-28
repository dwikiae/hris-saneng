<?php

namespace App\Repositories\Eloquent;

use App\Models\InstanceSetting;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use Illuminate\Support\Collection;

class InstanceSettingsRepository implements InstanceSettingsRepositoryInterface
{
    public const DEFAULTS = [
        'setup_completed' => false,
        'setup_completed_at' => null,
        'setup_completed_by' => null,
        'default_locale' => 'id',
        'mandatory_modules_installed' => false,
        'installed_mandatory_modules' => [],
        'storage_driver' => null,
        'storage_endpoint' => null,
        'smtp_host' => null,
        'smtp_port' => 587,
        'smtp_username' => null,
        'smtp_password' => null,
    ];

    public function __construct(private readonly InstanceSetting $model) {}

    public function get(string $key): mixed
    {
        $setting = $this->model->newQuery()->where('key', $key)->first();

        if ($setting === null) {
            return self::DEFAULTS[$key] ?? null;
        }

        return $this->castValue($key, $setting->getAttribute('value'));
    }

    /**
     * @return Collection<string, mixed>
     */
    public function getAll(): Collection
    {
        $stored = $this->model->newQuery()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key');

        return collect(self::DEFAULTS)
            ->map(fn (mixed $default, string $key): mixed => $stored->has($key)
                ? $this->castValue($key, $stored->get($key))
                : $default);
    }

    public function set(string $key, mixed $value): void
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            return;
        }

        $this->model->newQuery()->updateOrCreate(
            ['key' => $key],
            ['value' => $this->serializeValue($value)]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set((string) $key, $value);
        }
    }

    private function castValue(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $default = self::DEFAULTS[$key] ?? null;

        return match (true) {
            is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            is_int($default) => (int) $value,
            is_array($default) => json_decode((string) $value, true) ?: [],
            default => $value === '' ? null : $value,
        };
    }

    private function serializeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}
