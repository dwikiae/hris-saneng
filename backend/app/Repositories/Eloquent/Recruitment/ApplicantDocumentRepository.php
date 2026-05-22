<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\ApplicantDocument;
use App\Repositories\Contracts\Recruitment\ApplicantDocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ApplicantDocumentRepository implements ApplicantDocumentRepositoryInterface
{
    public function __construct(private readonly ApplicantDocument $model) {}

    public function findByToken(string $token): ?ApplicantDocument
    {
        /** @var ApplicantDocument|null $document */
        $document = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('token', $token)
            ->first();

        return $document;
    }

    public function listByApplicant(int $applicantId): Collection
    {
        /** @var Collection<int, ApplicantDocument> $documents */
        $documents = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('applicant_id', $applicantId)
            ->orderBy('document_type')
            ->get();

        return $documents;
    }

    public function upsert(int $applicantId, string $documentType, array $data): ApplicantDocument
    {
        /** @var ApplicantDocument $document */
        $document = $this->model->newQuery()->updateOrCreate(
            [
                'company_id' => (int) ($data['company_id'] ?? $this->defaultCompanyId()),
                'applicant_id' => $applicantId,
                'document_type' => $documentType,
            ],
            $data
        );

        return $document;
    }

    public function upsertPortalToken(int $applicantId, array $documentTypes, array $data): ApplicantDocument
    {
        $firstDocument = null;

        foreach ($documentTypes as $documentType) {
            $document = $this->upsert($applicantId, $documentType, $data);

            if (! $firstDocument instanceof ApplicantDocument) {
                $firstDocument = $document;
            }
        }

        if ($firstDocument instanceof ApplicantDocument) {
            return $firstDocument;
        }

        return $this->upsert($applicantId, 'ktp', $data);
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
