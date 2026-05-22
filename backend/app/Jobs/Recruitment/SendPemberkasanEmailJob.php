<?php

namespace App\Jobs\Recruitment;

use App\Mail\Recruitment\RecruitmentCandidateMail;
use App\Models\Company;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPemberkasanEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $applicantDocumentId,
        public readonly string $token
    ) {}

    public function handle(): void
    {
        $document = $this->document();
        $applicant = $document->getRelation('applicant');

        if (! $applicant instanceof Applicant) {
            return;
        }

        $companyName = $this->companyName((int) $document->getAttribute('company_id'));

        Mail::to((string) $applicant->getAttribute('email'))->send(new RecruitmentCandidateMail(
            'Portal Pemberkasan',
            'emails.recruitment.pemberkasan-link',
            [
                'title' => 'Portal Pemberkasan',
                'applicantName' => (string) $applicant->getAttribute('name'),
                'companyName' => $companyName,
                'portalUrl' => $this->url('/pemberkasan/'.$this->token),
            ]
        ));
    }

    private function document(): ApplicantDocument
    {
        /** @var ApplicantDocument $document */
        $document = ApplicantDocument::query()
            ->with('applicant')
            ->findOrFail($this->applicantDocumentId);

        return $document;
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
