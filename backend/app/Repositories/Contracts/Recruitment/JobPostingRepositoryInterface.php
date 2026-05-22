<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\JobPosting;
use Illuminate\Database\Eloquent\Collection;

interface JobPostingRepositoryInterface
{
    public function findById(int $id): ?JobPosting;

    public function findArchivedById(int $id): ?JobPosting;

    public function findByIdWithCriteria(int $id): ?JobPosting;

    /**
     * @return Collection<int, JobPosting>
     */
    public function listPublished(int $companyId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JobPosting;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JobPosting $jobPosting, array $data): JobPosting;

    public function archive(JobPosting $jobPosting, int $archivedBy): void;

    /**
     * @return Collection<int, JobPosting>
     */
    public function listPublishedExpired(int $companyId): Collection;

    public function hardDelete(JobPosting $jobPosting): void;
}
