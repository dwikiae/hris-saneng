<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\Test;
use App\Models\Recruitment\TestQuestion;
use Illuminate\Pagination\LengthAwarePaginator;

interface TestRepositoryInterface
{
    public function findById(int $id, ?int $companyId = null): ?Test;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator;

    public function findWithQuestions(int $id): ?Test;

    public function isLockedByPublishedPosting(int $testId, int $companyId): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Test;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Test $test, array $data): Test;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createQuestion(Test $test, array $data): TestQuestion;

    public function findQuestionById(int $questionId): ?TestQuestion;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(TestQuestion $question, array $data): TestQuestion;

    public function archiveQuestion(TestQuestion $question, int $archivedBy): void;

    public function archive(Test $test, int $archivedBy): void;
}
