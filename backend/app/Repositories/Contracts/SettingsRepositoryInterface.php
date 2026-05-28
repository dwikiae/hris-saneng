<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface SettingsRepositoryInterface
{
    public function get(string $key): mixed;

    public function getForCompany(string $key, int $companyId): mixed;

    /**
     * @return Collection<string, mixed>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<string, mixed>
     */
    public function getAllForCompany(int $companyId): Collection;

    public function set(string $key, mixed $value): void;

    public function setForCompany(string $key, mixed $value, int $companyId): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setMany(array $data): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setManyForCompany(array $data, int $companyId): void;
}
