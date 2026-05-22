<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\QuizSession;

interface QuizSessionRepositoryInterface
{
    public function findByToken(string $token): ?QuizSession;

    public function findByTokenWithQuestions(string $token): ?QuizSession;

    public function findActiveByApplicant(int $applicantId): ?QuizSession;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): QuizSession;

    public function markStarted(QuizSession $session): void;

    public function markCompleted(QuizSession $session, string $score): void;

    public function markExpired(QuizSession $session): void;

    /**
     * @param  array<int, array<string, mixed>>  $answers
     */
    public function saveAnswers(QuizSession $session, array $answers): void;
}
