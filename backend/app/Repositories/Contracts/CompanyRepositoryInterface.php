<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

interface CompanyRepositoryInterface
{
    /**
     * @return Collection<int, Company>
     */
    public function all(?int $companyId = null): Collection;

    public function find(int $id, ?int $companyId = null): ?Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company;
}
