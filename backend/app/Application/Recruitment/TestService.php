<?php

namespace App\Application\Recruitment;

use App\Models\Recruitment\Test;
use App\Models\Recruitment\TestQuestion;
use App\Repositories\Contracts\Recruitment\TestRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class TestService
{
    public function __construct(private readonly TestRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $createdBy): Test
    {
        Gate::authorize('recruitment.test.create');

        return DB::transaction(fn (): Test => $this->repository->create(array_merge($data, [
            'company_id' => (int) ($data['company_id'] ?? config('app.company_id')),
            'created_by' => $createdBy,
        ])));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTest(array $data, int $createdBy): Test
    {
        return $this->create($data, $createdBy);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function index(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate(
            (int) config('app.company_id'),
            $filters,
            (int) ($filters['per_page'] ?? 15)
        );
    }

    public function show(int $id): Test
    {
        $test = $this->repository->findWithQuestions($id);

        if ($test === null) {
            throw (new ModelNotFoundException)->setModel(Test::class, $id);
        }

        return $test;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, int $updatedBy): Test
    {
        Gate::authorize('recruitment.test.update');

        return DB::transaction(function () use ($id, $data, $updatedBy): Test {
            $test = $this->findTest($id);
            $this->ensureUnlocked($test);

            return $this->repository->update($test, array_merge($data, [
                'updated_by' => $updatedBy,
            ]));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTest(int $testId, array $data, int $actorId): Test
    {
        return $this->update($testId, $data, $actorId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addQuestion(int $testId, array $data, int $createdBy): TestQuestion
    {
        Gate::authorize('recruitment.test.update');

        return DB::transaction(function () use ($testId, $data, $createdBy): TestQuestion {
            $test = $this->findTest($testId);
            $this->ensureUnlocked($test);

            return $this->repository->createQuestion($test, array_merge($data, [
                'created_by' => $createdBy,
            ]));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(int $questionId, array $data, int $updatedBy): TestQuestion
    {
        Gate::authorize('recruitment.test.update');

        return DB::transaction(function () use ($questionId, $data, $updatedBy): TestQuestion {
            $question = $this->findQuestion($questionId);
            $test = $this->findTest((int) $question->getAttribute('test_id'));
            $this->ensureUnlocked($test);

            return $this->repository->updateQuestion($question, array_merge($data, [
                'updated_by' => $updatedBy,
            ]));
        });
    }

    public function deleteQuestion(int $questionId, int $deletedBy): void
    {
        Gate::authorize('recruitment.test.update');

        DB::transaction(function () use ($questionId, $deletedBy): void {
            $question = $this->findQuestion($questionId);
            $test = $this->findTest((int) $question->getAttribute('test_id'));
            $this->ensureUnlocked($test);

            $this->repository->archiveQuestion($question, $deletedBy);
        });
    }

    public function archive(int $id, int $userId): Test
    {
        Gate::authorize('recruitment.test.update');

        return DB::transaction(function () use ($id, $userId): Test {
            $test = $this->findTest($id);
            $this->ensureUnlocked($test);

            $this->repository->archive($test, $userId);

            return $test;
        });
    }

    public function archiveTest(int $testId, int $actorId): void
    {
        $this->archive($testId, $actorId);
    }

    private function findTest(int $id): Test
    {
        $test = $this->repository->findById($id);

        if ($test === null) {
            throw (new ModelNotFoundException)->setModel(Test::class, $id);
        }

        return $test;
    }

    private function findQuestion(int $questionId): TestQuestion
    {
        $question = $this->repository->findQuestionById($questionId);

        if ($question === null) {
            throw (new ModelNotFoundException)->setModel(TestQuestion::class, $questionId);
        }

        return $question;
    }

    private function ensureUnlocked(Test $test): void
    {
        if ($this->repository->isLockedByPublishedPosting((int) $test->getKey(), (int) $test->getAttribute('company_id'))) {
            throw new InvalidArgumentException('recruitment.test.locked_by_published_posting');
        }
    }
}
