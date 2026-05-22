<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\ApplicantDocument;
use Illuminate\Database\Eloquent\Collection;

interface ApplicantDocumentRepositoryInterface
{
    public function findByToken(string $token): ?ApplicantDocument;

    /**
     * @return Collection<int, ApplicantDocument>
     */
    public function listByApplicant(int $applicantId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(int $applicantId, string $documentType, array $data): ApplicantDocument;

    /**
     * @param  array<int, string>  $documentTypes
     * @param  array<string, mixed>  $data
     */
    public function upsertPortalToken(int $applicantId, array $documentTypes, array $data): ApplicantDocument;
}
