<?php

namespace App\Jobs\Recruitment;

use App\Mail\Recruitment\RecruitmentCandidateMail;
use App\Models\Company;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\InterviewSchedule;
use App\Models\Recruitment\JobPosting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInterviewScheduleEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $scheduleId,
        public readonly string $token,
        public readonly ?string $hrWhatsappNumber
    ) {}

    public function handle(): void
    {
        $schedule = $this->schedule();
        $applicant = $schedule->getRelation('applicant');

        if (! $applicant instanceof Applicant) {
            return;
        }

        $jobPosting = $applicant->getRelation('jobPosting');
        $companyName = $this->companyName((int) $schedule->getAttribute('company_id'));
        $scheduledAt = $schedule->getAttribute('scheduled_at');

        Mail::to((string) $applicant->getAttribute('email'))->send(new RecruitmentCandidateMail(
            'Jadwal Interview',
            'emails.recruitment.interview-schedule',
            [
                'title' => 'Jadwal Interview',
                'applicantName' => (string) $applicant->getAttribute('name'),
                'positionTitle' => $jobPosting instanceof JobPosting ? (string) $jobPosting->getAttribute('title') : '-',
                'companyName' => $companyName,
                'scheduledAt' => $scheduledAt instanceof \DateTimeInterface ? $scheduledAt->format('d/m/Y H:i') : (string) $scheduledAt,
                'location' => (string) ($schedule->getAttribute('location') ?? '-'),
                'hrWhatsappNumber' => $this->hrWhatsappNumber ?? '-',
                'confirmationUrl' => $this->url('/interview/'.$this->token),
            ]
        ));
    }

    private function schedule(): InterviewSchedule
    {
        /** @var InterviewSchedule $schedule */
        $schedule = InterviewSchedule::query()
            ->with('applicant.jobPosting')
            ->findOrFail($this->scheduleId);

        return $schedule;
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
