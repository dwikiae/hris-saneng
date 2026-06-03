<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;

interface InstanceCompanyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;

    public function find(int $id): ?Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company;

    public function archive(Company $company): void;

    /**
     * @return array<string, mixed>
     */
    public function settingsFor(Company $company): array;

    /**
     * @param  array<string, mixed>  $settings
     */
    public function syncSettings(Company $company, array $settings): void;
}
