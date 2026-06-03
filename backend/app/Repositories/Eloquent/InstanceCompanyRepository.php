<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Repositories\Contracts\InstanceCompanyRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class InstanceCompanyRepository implements InstanceCompanyRepositoryInterface
{
    public function __construct(
        private readonly Company $model,
        private readonly ArchiveService $archiveService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with('settings')
            ->withCount('employees')
            ->orderBy('name');

        if (($filters['search'] ?? '') !== '') {
            $search = (string) $filters['search'];

            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('legal_name', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Company
    {
        /** @var Company|null $company */
        $company = $this->model->newQuery()
            ->with('settings')
            ->withCount('employees')
            ->find($id);

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

        return $company->load('settings')->loadCount('employees');
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

        return $company->refresh()->load('settings')->loadCount('employees');
    }

    public function archive(Company $company): void
    {
        $this->archiveService->archive($company);
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsFor(Company $company): array
    {
        return $company->settings
            ->mapWithKeys(fn (CompanySetting $setting): array => [
                (string) $setting->getAttribute('key') => $setting->getAttribute('value'),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function syncSettings(Company $company, array $settings): void
    {
        foreach ($settings as $key => $value) {
            CompanySetting::withoutCompanyScope()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'key' => $key,
                ],
                [
                    'value' => $this->serializeSettingValue($value),
                ]
            );
        }
    }

    private function serializeSettingValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
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
            ->withoutGlobalScope('not_archived')
            ->where('slug', $slug)
            ->when($ignoreCompanyId !== null, fn ($query) => $query->whereKeyNot($ignoreCompanyId))
            ->exists();
    }
}
