<?php

namespace App\Repositories\Eloquent;

use App\Core\Company\Application\CompanyContext;
use App\Models\CompanySetting;
use App\Models\User;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class SettingsRepository implements SettingsRepositoryInterface
{
    private const ENCRYPTED_KEYS = [
        'smtp_password',
    ];

    private const DEFAULTS = [
        'password_min_length' => 8,
        'password_requires_uppercase' => true,
        'password_requires_number' => true,
        'password_requires_symbol' => false,
        'max_login_attempts' => 5,
        'lockout_duration_minutes' => 15,
        'session_lifetime_minutes' => 120,
        'retention_employee_years' => 5,
        'retention_candidate_years' => 1,
        'retention_audit_years' => 2,
        'smtp_host' => null,
        'smtp_port' => 587,
        'smtp_username' => null,
        'smtp_password' => null,
        'smtp_from_address' => null,
        'smtp_from_name' => null,
        'storage_disk' => 'documents',
        'storage_max_upload_mb' => 10,
    ];

    public function __construct(
        private readonly CompanySetting $model,
        private readonly CompanyContext $companyContext,
    ) {}

    public function get(string $key): mixed
    {
        return $this->getForCompany($key, $this->currentCompanyId());
    }

    public function getForCompany(string $key, int $companyId): mixed
    {
        $setting = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->first();

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
        return $this->getAllForCompany($this->currentCompanyId());
    }

    /**
     * @return Collection<string, mixed>
     */
    public function getAllForCompany(int $companyId): Collection
    {
        $stored = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key');

        return collect(self::DEFAULTS)
            ->map(fn (mixed $default, string $key): mixed => $stored->has($key)
                ? $this->castValue($key, $stored->get($key))
                : $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setForCompany($key, $value, $this->currentCompanyId());
    }

    public function setForCompany(string $key, mixed $value, int $companyId): void
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            return;
        }

        $this->model->newQuery()->withoutGlobalScope('company')->updateOrCreate(
            [
                'company_id' => $companyId,
                'key' => $key,
            ],
            ['value' => $this->serializeValue($key, $value)]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setMany(array $data): void
    {
        $this->setManyForCompany($data, $this->currentCompanyId());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setManyForCompany(array $data, int $companyId): void
    {
        foreach ($data as $key => $value) {
            $this->setForCompany((string) $key, $value, $companyId);
        }
    }

    private function castValue(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            return Crypt::decryptString((string) $value);
        }

        $default = self::DEFAULTS[$key] ?? null;

        return match (true) {
            is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            is_int($default) => (int) $value,
            default => $value === '' ? null : $value,
        };
    }

    private function serializeValue(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $serialized = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;

        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            return Crypt::encryptString($serialized);
        }

        return $serialized;
    }

    private function currentCompanyId(): int
    {
        if ($this->companyContext->hasCompany()) {
            return $this->companyContext->companyId();
        }

        $user = Auth::user();

        if ($user instanceof User && $user->company_id !== null) {
            return (int) $user->company_id;
        }

        return (int) config('company.default_id', 1);
    }
}
