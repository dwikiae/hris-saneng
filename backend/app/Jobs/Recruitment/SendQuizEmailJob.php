<?php

namespace App\Jobs\Recruitment;

use App\Mail\Recruitment\RecruitmentCandidateMail;
use App\Models\Company;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\QuizSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendQuizEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $quizSessionId) {}

    public function handle(): void
    {
        $session = $this->session();
        $applicant = $session->getRelation('applicant');

        if (! $applicant instanceof Applicant) {
            return;
        }

        $jobPosting = $applicant->getRelation('jobPosting');
        $companyName = $this->companyName((int) $session->getAttribute('company_id'));

        Mail::to((string) $applicant->getAttribute('email'))->send(new RecruitmentCandidateMail(
            'Undangan Tes Tulis',
            'emails.recruitment.quiz-link',
            [
                'title' => 'Undangan Tes Tulis',
                'applicantName' => (string) $applicant->getAttribute('name'),
                'positionTitle' => $jobPosting instanceof JobPosting ? (string) $jobPosting->getAttribute('title') : '-',
                'companyName' => $companyName,
                'timerMinutes' => (int) $session->getAttribute('timer_snapshot'),
                'quizUrl' => $this->url('/quiz/'.(string) $session->getAttribute('token')),
            ]
        ));
    }

    private function session(): QuizSession
    {
        /** @var QuizSession $session */
        $session = QuizSession::query()
            ->with('applicant.jobPosting')
            ->findOrFail($this->quizSessionId);

        return $session;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    private function companyName(int $companyId): string
    {
        /** @var Company|null $company */
        $company = Company::query()->find($companyId);

        return $company instanceof Company ? (string) $company->getAttribute('name') : 'PT Saneng';
    }
}
