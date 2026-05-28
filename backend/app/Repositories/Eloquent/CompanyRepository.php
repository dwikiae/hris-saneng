<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(private readonly Company $model) {}

    /**
     * @return Collection<int, Company>
     */
    public function all(?int $companyId = null): Collection
    {
        $query = $this->model->newQuery()->orderBy('name');

        if ($companyId !== null) {
            $query->where('id', $companyId);
        }

        /** @var Collection<int, Company> $companies */
        $companies = $query->get();

        return $companies;
    }

    public function find(int $id, ?int $companyId = null): ?Company
    {
        $query = $this->model->newQuery()->where('id', $id);

        if ($companyId !== null) {
            $query->where('id', $companyId);
        }

        /** @var Company|null $company */
        $company = $query->first();

        return $company;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        /** @var Company $company */
        $company = $this->model->newQuery()->create($data);

        return $company;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->refresh();
    }
}
