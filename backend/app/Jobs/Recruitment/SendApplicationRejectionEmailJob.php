<?php

namespace App\Jobs\Recruitment;

use App\Mail\Recruitment\RecruitmentCandidateMail;
use App\Models\Company;
use App\Models\Recruitment\Applicant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendApplicationRejectionEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $applicantId) {}

    public function handle(): void
    {
        $applicant = $this->applicant();
        $companyName = $this->companyName((int) $applicant->getAttribute('company_id'));

        Mail::to((string) $applicant->getAttribute('email'))->send(new RecruitmentCandidateMail(
            'Informasi Lamaran',
            'emails.recruitment.blacklist-rejection',
            [
                'title' => 'Informasi Lamaran Anda',
                'applicantName' => (string) $applicant->getAttribute('name'),
                'companyName' => $companyName,
            ]
        ));
    }

    private function applicant(): Applicant
    {
        /** @var Applicant $applicant */
        $applicant = Applicant::query()->findOrFail($this->applicantId);

        return $applicant;
    }

    private function companyName(int $companyId): string
    {
        /** @var Company|null $company */
        $company = Company::query()->find($companyId);

        return $company instanceof Company ? (string) $company->getAttribute('name') : 'PT Saneng';
    }
}
