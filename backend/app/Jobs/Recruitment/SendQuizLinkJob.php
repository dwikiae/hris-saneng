<?php

namespace App\Jobs\Recruitment;

use App\Application\Recruitment\QuizService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendQuizLinkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $applicantId, public readonly int $userId) {}

    public function handle(QuizService $quizService): void
    {
        $quizService->sendQuizLink($this->applicantId, $this->userId);
    }
}
