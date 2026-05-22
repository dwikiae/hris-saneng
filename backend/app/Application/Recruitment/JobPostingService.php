<?php

namespace App\Application\Recruitment;

use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\Recruitment\JobPostingRepositoryInterface;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class JobPostingService
{
    public function __construct(private readonly JobPostingRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $createdBy): JobPosting
    {
        return DB::transaction(fn (): JobPosting => $this->repository->create(array_merge($data, [
            'company_id' => (int) ($data['company_id'] ?? config('app.company_id')),
            'created_by' => $createdBy,
            'status' => 'draft',
        ])));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, int $createdBy): JobPosting
    {
        return $this->create($data, $createdBy);
    }

    /**
     * @return Collection<int, JobPosting>
     */
    public function index(): Collection
    {
        return $this->repository->listPublished((int) config('app.company_id'));
    }

    public function show(int $id): JobPosting
    {
        return $this->findActive($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, int $updatedBy): JobPosting
    {
        return DB::transaction(function () use ($id, $data, $updatedBy): JobPosting {
            $jobPosting = $this->findActive($id);

            if ($this->statusValue($jobPosting) === 'published') {
                throw new InvalidArgumentException('recruitment.job_posting.published_cannot_be_updated');
            }

            unset($data['status']);

            return $this->repository->update($jobPosting, array_merge($data, [
                'updated_by' => $updatedBy,
            ]));
        });
    }

    public function publish(int $id, int $userId): JobPosting
    {
        Gate::authorize('recruitment.job_posting.publish');

        return DB::transaction(function () use ($id, $userId): JobPosting {
            $jobPosting = $this->findActive($id);

            if ($this->statusValue($jobPosting) !== 'draft') {
                throw new InvalidArgumentException('recruitment.job_posting.only_draft_can_be_published');
            }

            if ($jobPosting->getAttribute('test_id') === null) {
                throw new InvalidArgumentException('recruitment.job_posting.test_required_before_publish');
            }

            return $this->repository->update($jobPosting, [
                'status' => 'published',
                'published_at' => now(),
                'updated_by' => $userId,
            ]);
        });
    }

    public function unpublish(int $id, int $userId): JobPosting
    {
        return DB::transaction(function () use ($id, $userId): JobPosting {
            $jobPosting = $this->findActive($id);

            if ($this->statusValue($jobPosting) !== 'published') {
                throw new InvalidArgumentException('recruitment.job_posting.only_published_can_be_unpublished');
            }

            return $this->repository->update($jobPosting, [
                'status' => 'draft',
                'updated_by' => $userId,
            ]);
        });
    }

    public function archive(int $id, int $userId): JobPosting
    {
        Gate::authorize('recruitment.job_posting.archive');

        return DB::transaction(function () use ($id, $userId): JobPosting {
            $jobPosting = $this->findActive($id);

            if ($this->statusValue($jobPosting) !== 'draft') {
                throw new InvalidArgumentException('recruitment.job_posting.only_draft_can_be_archived');
            }

            $this->repository->archive($jobPosting, $userId);

            return $jobPosting;
        });
    }

    public function expireOverdue(): void
    {
        $companyId = (int) config('app.company_id');

        DB::transaction(function () use ($companyId): void {
            foreach ($this->repository->listPublishedExpired($companyId) as $jobPosting) {
                $this->repository->update($jobPosting, [
                    'status' => 'draft',
                    'updated_by' => null,
                ]);
            }
        });
    }

    public function handleExpiredPostings(): void
    {
        $this->expireOverdue();
    }

    public function hardDelete(int $id, int $userId): void
    {
        Gate::authorize('recruitment.job_posting.archive');

        DB::transaction(function () use ($id, $userId): void {
            $jobPosting = $this->repository->findArchivedById($id);

            if ($jobPosting === null) {
                throw (new ModelNotFoundException)->setModel(JobPosting::class, $id);
            }

            activity()
                ->useLog('job_postings')
                ->performedOn($jobPosting)
                ->event('hard_deleted')
                ->withProperties(['deleted_by' => $userId])
                ->log('job_postings.hard_deleted');

            $this->repository->hardDelete($jobPosting);
        });
    }

    private function findActive(int $id): JobPosting
    {
        $jobPosting = $this->repository->findById($id);

        if ($jobPosting === null) {
            throw (new ModelNotFoundException)->setModel(JobPosting::class, $id);
        }

        return $jobPosting;
    }

    private function statusValue(JobPosting $jobPosting): string
    {
        $status = $jobPosting->getAttribute('status');

        return $status instanceof BackedEnum
            ? (string) $status->value
            : (string) $status;
    }
}
