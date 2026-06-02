<?php

namespace App\Application\Recruitment;

use App\Core\FileStorage\Application\FileStorageService;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantStageAttachment;
use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantStageAttachmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StageAttachmentService
{
    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly ApplicantStageAttachmentRepositoryInterface $attachments,
        private readonly FileStorageService $storage
    ) {}

    public function upload(int $applicantId, string $stage, UploadedFile $file, int $actorId): ApplicantStageAttachment
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return DB::transaction(function () use ($applicantId, $stage, $file, $actorId): ApplicantStageAttachment {
            $applicant = $this->findApplicant($applicantId);
            $jobPosting = $applicant->getRelation('jobPosting');
            $storedFile = $this->storage->storePrivateDocument(
                $file,
                $this->storage->recruitmentStageDirectory(
                    $jobPosting instanceof JobPosting ? (int) $jobPosting->getKey() : (int) $applicant->getAttribute('job_posting_id'),
                    (int) $applicant->getKey(),
                    $stage
                )
            );

            return $this->attachments->create([
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicant->getKey(),
                'stage' => $stage,
                'original_filename' => $storedFile->originalFilename,
                'storage_disk' => $storedFile->disk,
                'path' => $storedFile->path,
                'mime_type' => $storedFile->mimeType,
                'size_bytes' => $storedFile->sizeBytes,
                'uploaded_by' => $actorId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        });
    }

    private function findApplicant(int $applicantId): Applicant
    {
        $applicant = $this->applicants->findByIdWithPipelineRelations($applicantId);

        if ($applicant === null) {
            throw (new ModelNotFoundException)->setModel(Applicant::class, $applicantId);
        }

        return $applicant;
    }
}
