<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface SettingsRepositoryInterface
{
    public function get(string $key): mixed;

    /**
     * @return Collection<string, mixed>
     */
    public function getAll(): Collection;

    public function set(string $key, mixed $value): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setMany(array $data): void;
}
