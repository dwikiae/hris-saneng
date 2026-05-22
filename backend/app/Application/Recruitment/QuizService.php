<?php

namespace App\Application\Recruitment;

use App\Jobs\Recruitment\SendQuizEmailJob;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\QuizSession;
use App\Models\Recruitment\Test;
use App\Models\Recruitment\TestQuestion;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\Recruitment\QuizSessionRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class QuizService
{
    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly QuizSessionRepositoryInterface $sessions,
        private readonly SettingsRepositoryInterface $settings,
        private readonly DecimalMath $decimalMath
    ) {}

    public function sendQuizLink(int $applicantId, int $userId): QuizSession
    {
        return DB::transaction(function () use ($applicantId, $userId): QuizSession {
            $applicant = $this->findApplicant($applicantId);
            $jobPosting = $applicant->getRelation('jobPosting');
            $test = $jobPosting instanceof JobPosting ? $jobPosting->getRelation('test') : null;

            if (! $test instanceof Test) {
                throw new InvalidArgumentException('recruitment.quiz.test_not_assigned');
            }

            $activeSession = $this->sessions->findActiveByApplicant($applicantId);

            if ($activeSession instanceof QuizSession) {
                $this->sessions->markExpired($activeSession);
            }

            $session = $this->sessions->create([
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicantId,
                'test_id' => $test->getKey(),
                'token' => (string) Str::uuid(),
                'status' => 'pending',
                'timer_snapshot' => (int) $test->getAttribute('duration_minutes'),
                'passing_grade_snapshot' => (string) $test->getAttribute('passing_grade'),
                'expires_at' => now()->addHours($this->linkExpiresHours()),
                'created_by' => $userId,
            ]);

            SendQuizEmailJob::dispatch((int) $session->getKey());

            return $session;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuizForCandidate(string $token): array
    {
        return DB::transaction(function () use ($token): array {
            $session = $this->findSessionWithQuestions($token);
            $this->ensureUsableToken($session, ['pending', 'in_progress']);

            if ($this->statusValue($session) === 'pending') {
                $this->sessions->markStarted($session);
                $session = $this->findSessionWithQuestions($token);
            }

            $questions = $this->questions($session)->shuffle()->values()->map(
                fn (TestQuestion $question): array => [
                    'id' => $question->getKey(),
                    'type' => $question->getAttribute('type'),
                    'question' => $question->getAttribute('question'),
                    'pilihan' => $question->getAttribute('pilihan'),
                    'bobot' => $question->getAttribute('bobot'),
                ]
            )->all();

            return [
                'token' => $token,
                'timer' => $session->getAttribute('timer_snapshot'),
                'questions' => $questions,
            ];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $answers
     */
    public function submitQuiz(string $token, array $answers): QuizSession
    {
        return DB::transaction(function () use ($token, $answers): QuizSession {
            $session = $this->findSessionWithQuestions($token);
            $this->ensureUsableToken($session, ['pending', 'in_progress']);

            if ($this->statusValue($session) === 'pending') {
                $this->sessions->markStarted($session);
                $session = $this->findSessionWithQuestions($token);
            }

            $gradedAnswers = $this->gradeAnswers($session, $answers);
            $this->sessions->saveAnswers($session, $gradedAnswers['answers']);
            $this->sessions->markCompleted($session, $gradedAnswers['score']);

            $session = $this->findSessionWithQuestions($token);

            $applicant = $session->getRelation('applicant');

            if (! $applicant instanceof Applicant) {
                throw new InvalidArgumentException('recruitment.quiz.applicant_not_found');
            }

            if ($this->decimalMath->greaterThan($gradedAnswers['score'], (string) $session->getAttribute('passing_grade_snapshot'))) {
                $this->applicants->update($applicant, ['stage' => 'interview']);
            } else {
                $this->applicants->update($applicant, [
                    'stage' => 'rejected',
                    'status' => 'failed',
                    'rejection_reason' => 'recruitment.quiz.score_below_passing_grade',
                ]);
            }

            return $session->refresh();
        });
    }

    public function resendQuizLink(int $applicantId, int $userId): QuizSession
    {
        $activeSession = $this->sessions->findActiveByApplicant($applicantId);

        if ($activeSession instanceof QuizSession && ! $this->isExpired($activeSession)) {
            throw new InvalidArgumentException('recruitment.quiz.link_still_active');
        }

        return $this->sendQuizLink($applicantId, $userId);
    }

    private function findApplicant(int $applicantId): Applicant
    {
        $applicant = $this->applicants->findByIdWithPipelineRelations($applicantId);

        if ($applicant === null) {
            throw (new ModelNotFoundException)->setModel(Applicant::class, $applicantId);
        }

        return $applicant;
    }

    private function findSessionWithQuestions(string $token): QuizSession
    {
        $session = $this->sessions->findByTokenWithQuestions($token);

        if ($session === null) {
            throw (new ModelNotFoundException)->setModel(QuizSession::class);
        }

        return $session;
    }

    /**
     * @param  array<int, string>  $allowedStatuses
     */
    private function ensureUsableToken(QuizSession $session, array $allowedStatuses): void
    {
        if ($this->isExpired($session)) {
            throw new InvalidArgumentException('recruitment.quiz.token_expired');
        }

        if (! in_array($this->statusValue($session), $allowedStatuses, true)) {
            throw new InvalidArgumentException('recruitment.quiz.invalid_status');
        }
    }

    private function isExpired(QuizSession $session): bool
    {
        $expiresAt = $session->getAttribute('expires_at');

        return $expiresAt !== null && $expiresAt->isPast();
    }

    /**
     * @return Collection<int, TestQuestion>
     */
    private function questions(QuizSession $session): Collection
    {
        $test = $session->getRelation('test');
        /** @var Collection<int, TestQuestion> $questions */
        $questions = $test instanceof Test ? $test->getRelation('questions') : new Collection;

        return $questions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $submittedAnswers
     * @return array{score: string, answers: array<int, array<string, mixed>>}
     */
    private function gradeAnswers(QuizSession $session, array $submittedAnswers): array
    {
        $answersByQuestion = collect($submittedAnswers)
            ->keyBy(fn (array $answer): int => (int) $answer['test_question_id']);

        $totalBobot = '0.00';
        $correctBobot = '0.00';
        $answers = [];

        foreach ($this->questions($session) as $question) {
            $questionId = (int) $question->getKey();
            $bobot = (string) $question->getAttribute('bobot');
            $submitted = $answersByQuestion->get($questionId, []);
            $answer = (string) ($submitted['answer'] ?? '');
            $isCorrect = hash_equals((string) $question->getAttribute('answer_key'), $answer);

            $totalBobot = $this->decimalMath->add($totalBobot, $bobot);

            if ($isCorrect) {
                $correctBobot = $this->decimalMath->add($correctBobot, $bobot);
            }

            $answers[] = [
                'test_question_id' => $questionId,
                'answer' => $answer,
                'is_correct' => $isCorrect,
                'bobot_snapshot' => $bobot,
                'score' => $isCorrect ? $bobot : '0.00',
            ];
        }

        if ($this->decimalMath->compare($totalBobot, '0.00') === 0) {
            return ['score' => '0.00', 'answers' => $answers];
        }

        $score = $this->decimalMath->mul($this->decimalMath->div($correctBobot, $totalBobot, 4), '10', 2);

        return ['score' => $score, 'answers' => $answers];
    }

    private function linkExpiresHours(): int
    {
        return max(1, (int) ($this->settings->get('recruitment_link_expires_hours') ?? 72));
    }

    private function statusValue(QuizSession $session): string
    {
        $status = $session->getAttribute('status');

        return $status instanceof \BackedEnum ? (string) $status->value : (string) $status;
    }

}
