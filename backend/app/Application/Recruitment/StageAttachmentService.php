<?php

namespace App\Application\Recruitment;

use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantStageAttachment;
use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantStageAttachmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class StageAttachmentService
{
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    /**
     * @var array<int, string>
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly ApplicantStageAttachmentRepositoryInterface $attachments
    ) {}

    public function upload(int $applicantId, string $stage, UploadedFile $file, int $actorId): ApplicantStageAttachment
    {
        Gate::authorize('recruitment.applicant.update_stage');
        $this->validateFile($file);

        return DB::transaction(function () use ($applicantId, $stage, $file, $actorId): ApplicantStageAttachment {
            $applicant = $this->findApplicant($applicantId);
            $jobPosting = $applicant->getRelation('jobPosting');
            $path = sprintf(
                'recruitment/%d/applicants/%d/stages/%s/%s',
                $jobPosting instanceof JobPosting ? (int) $jobPosting->getKey() : (int) $applicant->getAttribute('job_posting_id'),
                (int) $applicant->getKey(),
                $stage,
                uniqid().'_'.$file->getClientOriginalName()
            );

            Storage::disk('documents')->put($path, $file->getContent());

            return $this->attachments->create([
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicant->getKey(),
                'stage' => $stage,
                'original_filename' => $file->getClientOriginalName(),
                'storage_disk' => 'documents',
                'path' => $path,
                'mime_type' => (string) $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => $actorId,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        });
    }

    private function validateFile(UploadedFile $file): void
    {
        if (! in_array((string) $file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('recruitment.attachment.invalid_mime_type');
        }

        if ((int) $file->getSize() > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('recruitment.attachment.file_too_large');
        }
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
