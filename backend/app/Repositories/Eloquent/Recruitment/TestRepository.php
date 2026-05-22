<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Recruitment\TestQuestion;
use App\Repositories\Contracts\Recruitment\TestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TestRepository implements TestRepositoryInterface
{
    public function __construct(
        private readonly RecruitmentTest $model,
        private readonly JobPosting $jobPosting
    ) {}

    public function findById(int $id, ?int $companyId = null): ?RecruitmentTest
    {
        /** @var RecruitmentTest|null $test */
        $test = $this->model->newQuery()
            ->where('company_id', $companyId ?? $this->defaultCompanyId())
            ->where('id', $id)
            ->first();

        return $test;
    }

    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('company_id', $companyId)
            ->orderBy('name');

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate($perPage);
    }

    public function findWithQuestions(int $id): ?RecruitmentTest
    {
        /** @var RecruitmentTest|null $test */
        $test = $this->model->newQuery()
            ->with('questions')
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $id)
            ->first();

        return $test;
    }

    public function isLockedByPublishedPosting(int $testId, int $companyId): bool
    {
        return $this->jobPosting->newQuery()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('test_id', $testId)
            ->where('status', 'published')
            ->exists();
    }

    public function create(array $data): RecruitmentTest
    {
        /** @var RecruitmentTest $test */
        $test = $this->model->newQuery()->create($data);

        return $test;
    }

    public function update(RecruitmentTest $test, array $data): RecruitmentTest
    {
        $test->update($data);

        return $test->refresh();
    }

    public function createQuestion(RecruitmentTest $test, array $data): TestQuestion
    {
        /** @var TestQuestion $question */
        $question = $test->questions()->create(array_merge($data, [
            'company_id' => $test->getAttribute('company_id'),
        ]));

        return $question;
    }

    public function findQuestionById(int $questionId): ?TestQuestion
    {
        /** @var TestQuestion|null $question */
        $question = TestQuestion::query()
            ->where('company_id', $this->defaultCompanyId())
            ->where('id', $questionId)
            ->first();

        return $question;
    }

    public function updateQuestion(TestQuestion $question, array $data): TestQuestion
    {
        $question->update($data);

        return $question->refresh();
    }

    public function archiveQuestion(TestQuestion $question, int $archivedBy): void
    {
        $question->update([
            'archived_at' => now(),
            'archived_by' => $archivedBy,
        ]);
    }

    public function archive(RecruitmentTest $test, int $archivedBy): void
    {
        $test->update([
            'archived_at' => now(),
            'archived_by' => $archivedBy,
        ]);
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
