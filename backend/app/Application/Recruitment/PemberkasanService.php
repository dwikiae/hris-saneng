<?php

namespace App\Application\Recruitment;

use App\Enums\Recruitment\ApplicantDocumentType;
use App\Jobs\Recruitment\SendPemberkasanEmailJob;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantDocument;
use App\Repositories\Contracts\Recruitment\ApplicantDocumentRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PemberkasanService
{
    /**
     * @var array<int, string>
     */
    private const REQUIRED_DOCUMENT_TYPES = [
        'ktp',
        'kk',
        'npwp',
        'rekening',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
    ];

    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly ApplicantDocumentRepositoryInterface $documents,
        private readonly SettingsRepositoryInterface $settings,
        private readonly ApplicantService $applicantService
    ) {}

    public function sendPemberkasanLink(int $applicantId, int $userId): ApplicantDocument
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return DB::transaction(function () use ($applicantId, $userId): ApplicantDocument {
            $applicant = $this->findApplicant($applicantId);
            $this->ensurePemberkasanStage($applicant);

            $token = (string) Str::uuid();
            $document = $this->documents->upsertPortalToken($applicantId, self::REQUIRED_DOCUMENT_TYPES, [
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicantId,
                'token' => $token,
                'expires_at' => now()->addHours($this->linkExpiresHours()),
                'updated_by' => $userId,
                'created_by' => $userId,
            ]);

            SendPemberkasanEmailJob::dispatch((int) $document->getKey(), $token);

            return $document;
        });
    }

    public function resendPemberkasanLink(int $applicantId, int $userId): ApplicantDocument
    {
        $documents = $this->documents->listByApplicant($applicantId);
        $tokenDocument = $documents->first(fn (ApplicantDocument $document): bool => $document->getAttribute('token') !== null);

        if ($tokenDocument instanceof ApplicantDocument && ! $this->isExpired($tokenDocument)) {
            throw new InvalidArgumentException('recruitment.pemberkaasan.link_still_active');
        }

        return $this->sendPemberkasanLink($applicantId, $userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPortalData(string $token): array
    {
        $tokenDocument = $this->findDocumentByToken($token);
        $this->ensureUsableToken($tokenDocument);

        $documents = $this->documents->listByApplicant((int) $tokenDocument->getAttribute('applicant_id'));

        return [
            'applicant_id' => (int) $tokenDocument->getAttribute('applicant_id'),
            'documents' => $this->documentStatuses($documents),
        ];
    }

    public function uploadDocument(string $token, string $documentType, string $filePath, ?int $uploadedBy): ApplicantDocument
    {
        return DB::transaction(function () use ($token, $documentType, $filePath, $uploadedBy): ApplicantDocument {
            $tokenDocument = $this->findDocumentByToken($token);
            $this->ensureUsableToken($tokenDocument);
            $this->ensureValidDocumentType($documentType);

            return $this->documents->upsert((int) $tokenDocument->getAttribute('applicant_id'), $documentType, [
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => (int) $tokenDocument->getAttribute('applicant_id'),
                'document_type' => $documentType,
                'token' => $token,
                'expires_at' => $tokenDocument->getAttribute('expires_at'),
                'path' => $filePath,
                'uploaded_by' => $uploadedBy,
                'uploaded_at' => now(),
                'updated_by' => $uploadedBy,
            ]);
        });
    }

    public function verifyAndHire(int $applicantId, int $userId): Applicant
    {
        Gate::authorize('recruitment.applicant.update_stage');

        $applicant = $this->findApplicant($applicantId);
        $this->ensurePemberkasanStage($applicant);

        return $this->applicantService->hireApplicant($applicantId, $userId);
    }

    private function findApplicant(int $applicantId): Applicant
    {
        $applicant = $this->applicants->findByIdWithPipelineRelations($applicantId);

        if ($applicant === null) {
            throw (new ModelNotFoundException)->setModel(Applicant::class, $applicantId);
        }

        return $applicant;
    }

    private function findDocumentByToken(string $token): ApplicantDocument
    {
        $document = $this->documents->findByToken($token);

        if ($document === null) {
            throw (new ModelNotFoundException)->setModel(ApplicantDocument::class);
        }

        return $document;
    }

    private function ensurePemberkasanStage(Applicant $applicant): void
    {
        if ($this->stageValue($applicant) !== 'pemberkasan') {
            throw new InvalidArgumentException('recruitment.pemberkaasan.requires_pemberkasan_stage');
        }
    }

    private function ensureUsableToken(ApplicantDocument $document): void
    {
        if ($this->isExpired($document)) {
            throw new InvalidArgumentException('recruitment.pemberkaasan.token_expired');
        }
    }

    private function ensureValidDocumentType(string $documentType): void
    {
        if (ApplicantDocumentType::tryFrom($documentType) === null || ! in_array($documentType, self::REQUIRED_DOCUMENT_TYPES, true)) {
            throw new InvalidArgumentException('recruitment.pemberkaasan.invalid_document_type');
        }
    }

    private function isExpired(ApplicantDocument $document): bool
    {
        $expiresAt = $document->getAttribute('expires_at');

        return $expiresAt !== null && $expiresAt->isPast();
    }

    /**
     * @param  Collection<int, ApplicantDocument>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function documentStatuses(Collection $documents): array
    {
        $documentsByType = $documents->keyBy(
            fn (ApplicantDocument $document): string => $this->documentTypeValue($document)
        );

        return collect(self::REQUIRED_DOCUMENT_TYPES)
            ->map(function (string $documentType) use ($documentsByType): array {
                $document = $documentsByType->get($documentType);

                return [
                    'document_type' => $documentType,
                    'uploaded_at' => $document instanceof ApplicantDocument ? $document->getAttribute('uploaded_at') : null,
                    'file_path' => $document instanceof ApplicantDocument ? $document->getAttribute('path') : null,
                ];
            })
            ->values()
            ->all();
    }

    private function documentTypeValue(ApplicantDocument $document): string
    {
        $documentType = $document->getAttribute('document_type');

        return $documentType instanceof \BackedEnum ? (string) $documentType->value : (string) $documentType;
    }

    private function stageValue(Applicant $applicant): string
    {
        $stage = $applicant->getAttribute('stage');

        return $stage instanceof \BackedEnum ? (string) $stage->value : (string) $stage;
    }

    private function linkExpiresHours(): int
    {
        return max(1, (int) ($this->settings->get('recruitment_link_expires_hours') ?? 72));
    }
}
