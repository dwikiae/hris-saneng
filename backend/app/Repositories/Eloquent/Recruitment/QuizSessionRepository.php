<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\QuizAnswer;
use App\Models\Recruitment\QuizSession;
use App\Repositories\Contracts\Recruitment\QuizSessionRepositoryInterface;

class QuizSessionRepository implements QuizSessionRepositoryInterface
{
    public function __construct(
        private readonly QuizSession $model,
        private readonly QuizAnswer $answer
    ) {}

    public function findByToken(string $token): ?QuizSession
    {
        /** @var QuizSession|null $session */
        $session = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('token', $token)
            ->first();

        return $session;
    }

    public function findByTokenWithQuestions(string $token): ?QuizSession
    {
        /** @var QuizSession|null $session */
        $session = $this->model->newQuery()
            ->with(['applicant.jobPosting', 'test.questions'])
            ->where('company_id', $this->defaultCompanyId())
            ->where('token', $token)
            ->first();

        return $session;
    }

    public function findActiveByApplicant(int $applicantId): ?QuizSession
    {
        /** @var QuizSession|null $session */
        $session = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('applicant_id', $applicantId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByDesc('created_at')
            ->first();

        return $session;
    }

    public function create(array $data): QuizSession
    {
        /** @var QuizSession $session */
        $session = $this->model->newQuery()->create($data);

        return $session;
    }

    public function markStarted(QuizSession $session): void
    {
        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(QuizSession $session, string $score): void
    {
        $session->update([
            'status' => 'completed',
            'submitted_at' => now(),
            'finished_at' => now(),
            'score' => $score,
            'nilai_akhir' => $score,
        ]);
    }

    public function markExpired(QuizSession $session): void
    {
        $session->update(['status' => 'expired']);
    }

    public function saveAnswers(QuizSession $session, array $answers): void
    {
        foreach ($answers as $answer) {
            $this->answer->newQuery()->updateOrCreate(
                [
                    'quiz_session_id' => $session->getKey(),
                    'test_question_id' => (int) $answer['test_question_id'],
                ],
                [
                    'company_id' => (int) $session->getAttribute('company_id'),
                    'answer' => $answer['answer'] ?? null,
                    'is_correct' => $answer['is_correct'] ?? null,
                    'bobot_snapshot' => $answer['bobot_snapshot'] ?? '0.00',
                    'score' => $answer['score'] ?? null,
                ]
            );
        }
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
