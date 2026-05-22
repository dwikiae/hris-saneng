<?php

namespace App\Jobs\Recruitment;

use App\Mail\Recruitment\RecruitmentCandidateMail;
use App\Models\Company;
use App\Models\Recruitment\Applicant;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendApplicationDuplicateEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $applicantId) {}

    public function handle(SettingsRepositoryInterface $settings): void
    {
        $applicant = $this->applicant();
        $companyName = $this->companyName((int) $applicant->getAttribute('company_id'));

        Mail::to((string) $applicant->getAttribute('email'))->send(new RecruitmentCandidateMail(
            'Informasi Lamaran Duplikat',
            'emails.recruitment.duplicate-applicant',
            [
                'title' => 'Lamaran Belum Dapat Diproses',
                'applicantName' => (string) $applicant->getAttribute('name'),
                'companyName' => $companyName,
                'retentionDays' => (int) ($settings->get('applicant_data_retention_days') ?? 365),
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
