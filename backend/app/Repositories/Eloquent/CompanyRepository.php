<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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

    public function findByIdentifier(string $identifier): ?Company
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        /** @var Company|null $company */
        $company = $this->model->newQuery()
            ->where(function ($query) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $query->whereKey((int) $identifier);

                    return;
                }

                $query->where('slug', $identifier);
            })
            ->first();

        return $company;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        $data = $this->normalizeSlug($data);

        /** @var Company $company */
        $company = $this->model->newQuery()->create($data);

        return $company;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data): Company
    {
        if (array_key_exists('slug', $data) || ($company->getAttribute('slug') === null && array_key_exists('name', $data))) {
            $data = $this->normalizeSlug($data, (int) $company->getKey());
        }

        $company->update($data);

        return $company->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSlug(array $data, ?int $ignoreCompanyId = null): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $base = Str::slug($slug !== '' ? $slug : (string) ($data['name'] ?? ''));
        $base = $base !== '' ? $base : 'company';

        $data['slug'] = $this->uniqueSlug($base, $ignoreCompanyId);

        return $data;
    }

    private function uniqueSlug(string $base, ?int $ignoreCompanyId): string
    {
        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreCompanyId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreCompanyId): bool
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->when($ignoreCompanyId !== null, fn ($query) => $query->whereKeyNot($ignoreCompanyId))
            ->exists();
    }
}
