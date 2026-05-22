<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\Recruitment\JobPostingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JobPostingRepository implements JobPostingRepositoryInterface
{
    public function __construct(private readonly JobPosting $model) {}

    public function findById(int $id): ?JobPosting
    {
        /** @var JobPosting|null $jobPosting */
        $jobPosting = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $id)
            ->first();

        return $jobPosting;
    }

    public function findArchivedById(int $id): ?JobPosting
    {
        /** @var JobPosting|null $jobPosting */
        $jobPosting = $this->model->newQuery()
            ->withoutGlobalScope('not_archived')
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $id)
            ->whereNotNull('archived_at')
            ->first();

        return $jobPosting;
    }

    public function findByIdWithCriteria(int $id): ?JobPosting
    {
        /** @var JobPosting|null $jobPosting */
        $jobPosting = $this->model->newQuery()
            ->with(['criteria', 'test'])
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $id)
            ->first();

        return $jobPosting;
    }

    public function listPublished(int $companyId): Collection
    {
        /** @var Collection<int, JobPosting> $jobPostings */
        $jobPostings = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get();

        return $jobPostings;
    }

    public function create(array $data): JobPosting
    {
        /** @var JobPosting $jobPosting */
        $jobPosting = $this->model->newQuery()->create($data);

        return $jobPosting;
    }

    public function update(JobPosting $jobPosting, array $data): JobPosting
    {
        $jobPosting->update($data);

        return $jobPosting->refresh();
    }

    public function archive(JobPosting $jobPosting, int $archivedBy): void
    {
        $jobPosting->update([
            'status' => 'archived',
            'archived_at' => now(),
            'archived_by' => $archivedBy,
        ]);
    }

    public function listPublishedExpired(int $companyId): Collection
    {
        /** @var Collection<int, JobPosting> $jobPostings */
        $jobPostings = $this->model->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'published')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->get();

        return $jobPostings;
    }

    public function hardDelete(JobPosting $jobPosting): void
    {
        $jobPosting->update([
            'status' => 'archived',
            'archived_at' => $jobPosting->getAttribute('archived_at') ?? now(),
        ]);
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
