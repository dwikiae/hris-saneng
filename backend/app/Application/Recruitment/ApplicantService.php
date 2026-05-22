<?php

namespace App\Application\Recruitment;

use App\Jobs\Recruitment\CreateDraftEmployeeJob;
use App\Jobs\Recruitment\SendApplicationConfirmationEmailJob;
use App\Jobs\Recruitment\SendApplicationDuplicateEmailJob;
use App\Jobs\Recruitment\SendApplicationRejectionEmailJob;
use App\Jobs\Recruitment\SendPemberkasanLinkJob;
use App\Jobs\Recruitment\SendQuizLinkJob;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\Recruitment\ApplicantBlacklistRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ApplicantService
{
    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly ApplicantBlacklistRepositoryInterface $blacklists,
        private readonly SettingsRepositoryInterface $settings
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitApplication(array $data, ?int $companyId = null): Applicant
    {
        $companyId ??= (int) config('app.company_id');

        return DB::transaction(function () use ($data, $companyId): Applicant {
            $email = (string) $data['email'];
            $whatsapp = (string) ($data['phone'] ?? $data['whatsapp'] ?? '');

            $blacklist = $this->applicants->findBlacklisted($email, $whatsapp, $companyId);

            if ($blacklist instanceof ApplicantBlacklist) {
                $applicant = $this->createApplicant($data, $companyId, [
                    'stage' => 'rejected',
                    'status' => 'blacklisted',
                    'is_blacklisted' => true,
                    'rejection_reason' => 'recruitment.applicant.blacklisted',
                ]);

                SendApplicationRejectionEmailJob::dispatch((int) $applicant->getKey());
                SendApplicationConfirmationEmailJob::dispatch((int) $applicant->getKey());

                return $applicant;
            }

            $duplicate = $this->applicants->findByEmailOrWhatsapp($email, $whatsapp, $companyId);

            if ($duplicate instanceof Applicant && ! $this->allowDuplicateApplicant()) {
                throw new InvalidArgumentException('recruitment.applicant.duplicate_not_allowed');
            }

            $stage = $this->passesAutoScreening($data) ? 'screening' : 'rejected';

            $applicant = $this->createApplicant($data, $companyId, [
                'stage' => $stage,
                'status' => $stage === 'rejected' ? 'failed' : 'active',
                'is_duplicate' => $duplicate instanceof Applicant,
                'rejection_reason' => $stage === 'rejected' ? 'recruitment.applicant.auto_screening_failed' : null,
            ]);

            if ($duplicate instanceof Applicant) {
                SendApplicationDuplicateEmailJob::dispatch((int) $applicant->getKey());
            }

            SendApplicationConfirmationEmailJob::dispatch((int) $applicant->getKey());

            return $applicant;
        });
    }

    public function advanceToTesTulis(int $applicantId, int $actorId): void
    {
        $this->advanceStage($applicantId, 'tes_tulis', $actorId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function index(array $filters): LengthAwarePaginator
    {
        return $this->applicants->listByJobPosting((int) ($filters['job_posting_id'] ?? 0), $filters);
    }

    public function show(int $applicantId): Applicant
    {
        return $this->findApplicant($applicantId);
    }

    public function resendQuizLink(int $applicantId, int $actorId): void
    {
        Gate::authorize('recruitment.applicant.update_stage');

        app(QuizService::class)->resendQuizLink($applicantId, $actorId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $answers
     */
    public function submitQuizAnswers(string $token, array $answers): \App\Models\Recruitment\QuizSession
    {
        return app(QuizService::class)->submitQuiz($token, $answers);
    }

    /**
     * @param  array<string, mixed>  $scheduleData
     */
    public function scheduleInterview(int $applicantId, array $scheduleData, int $actorId): \App\Models\Recruitment\InterviewSchedule
    {
        return app(InterviewService::class)->scheduleInterview($applicantId, $scheduleData, $actorId);
    }

    public function confirmInterviewAttendance(string $token, string $confirmation): void
    {
        app(InterviewService::class)->confirmAttendance($token, $confirmation);
    }

    /**
     * @param  array<string, mixed>|string|null  $notes
     */
    public function advanceStage(int $applicantId, string $targetStage, int $userId, array|string|null $notes = null): Applicant
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return DB::transaction(function () use ($applicantId, $targetStage, $userId, $notes): Applicant {
            $applicant = $this->findApplicant($applicantId);
            $fromStage = $this->stageValue($applicant);
            $actualTarget = $targetStage;

            if ($targetStage === 'hired') {
                throw new InvalidArgumentException('recruitment.applicant.use_hire_applicant');
            }

            $jobPosting = $applicant->getRelation('jobPosting');

            if ($targetStage === 'tes_kemampuan' && ! $this->isSkillTestRequired($jobPosting instanceof JobPosting ? $jobPosting : null)) {
                $actualTarget = 'interview';
            }

            $updated = $this->applicants->update($applicant, [
                'stage' => $actualTarget,
                'updated_by' => $userId,
            ]);

            activity()
                ->useLog('applicants')
                ->performedOn($updated)
                ->event('stage_changed')
                ->withProperties([
                    'changed_by' => $userId,
                    'from_stage' => $fromStage,
                    'to_stage' => $actualTarget,
                    'notes' => is_array($notes) ? $notes : ['notes' => $notes],
                ])
                ->log('applicants.stage_changed');

            if (in_array($actualTarget, ['test', 'tes_tulis'], true)) {
                SendQuizLinkJob::dispatch((int) $updated->getKey(), $userId);
            }

            if ($actualTarget === 'pemberkasan') {
                SendPemberkasanLinkJob::dispatch((int) $updated->getKey(), $userId);
            }

            return $updated;
        });
    }

    public function initiatePemberkasan(int $applicantId, int $actorId): void
    {
        app(PemberkasanService::class)->sendPemberkasanLink($applicantId, $actorId);
    }

    public function resendPemberkasanLink(int $applicantId, int $actorId): void
    {
        app(PemberkasanService::class)->resendPemberkasanLink($applicantId, $actorId);
    }

    public function uploadPemberkasanDocument(string $token, string $documentType, UploadedFile $file): \App\Models\Recruitment\ApplicantDocument
    {
        $portalData = app(PemberkasanService::class)->getPortalData($token);
        $applicant = $this->findApplicant((int) $portalData['applicant_id']);
        $jobPosting = $applicant->getRelation('jobPosting');
        $path = sprintf(
            'recruitment/%d/applicants/%d/pemberkasan/%s/%s',
            $jobPosting instanceof JobPosting ? (int) $jobPosting->getKey() : (int) $applicant->getAttribute('job_posting_id'),
            (int) $applicant->getKey(),
            $documentType,
            uniqid().'_'.$file->getClientOriginalName()
        );

        Storage::disk('documents')->put($path, $file->getContent());

        return app(PemberkasanService::class)->uploadDocument($token, $documentType, $path, null);
    }

    public function hireApplicant(int $applicantId, int $actorId): Applicant
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return DB::transaction(function () use ($applicantId, $actorId): Applicant {
            $applicant = $this->findApplicant($applicantId);
            $fromStage = $this->stageValue($applicant);

            if ($fromStage !== 'pemberkasan') {
                throw new InvalidArgumentException('recruitment.applicant.hired_requires_pemberkasan');
            }

            $updated = $this->applicants->update($applicant, [
                'stage' => 'hired',
                'updated_by' => $actorId,
            ]);

            activity()
                ->useLog('applicants')
                ->performedOn($updated)
                ->event('hired')
                ->withProperties([
                    'changed_by' => $actorId,
                    'from_stage' => $fromStage,
                    'to_stage' => 'hired',
                ])
                ->log('applicants.hired');

            CreateDraftEmployeeJob::dispatch((int) $updated->getKey(), $actorId);

            return $updated;
        });
    }

    public function rejectApplicant(int $applicantId, int $actorId, ?string $reason = null): Applicant
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return $this->reject($applicantId, $actorId, $reason);
    }

    public function restoreFromRejected(int $applicantId, string $toStage, int $actorId): Applicant
    {
        Gate::authorize('recruitment.applicant.update_stage');

        if ($toStage === 'hired') {
            throw new InvalidArgumentException('recruitment.applicant.hired_requires_pemberkasan');
        }

        return $this->advanceStage($applicantId, $toStage, $actorId, 'restore_from_rejected');
    }

    public function reject(int $applicantId, int $userId, ?string $reason = null): Applicant
    {
        return DB::transaction(function () use ($applicantId, $userId, $reason): Applicant {
            $applicant = $this->findApplicant($applicantId);
            $fromStage = $this->stageValue($applicant);

            $updated = $this->applicants->update($applicant, [
                'stage' => 'rejected',
                'status' => 'failed',
                'rejection_reason' => $reason,
                'updated_by' => $userId,
            ]);

            activity()
                ->useLog('applicants')
                ->performedOn($updated)
                ->event('rejected')
                ->withProperties(['changed_by' => $userId, 'from_stage' => $fromStage])
                ->log('applicants.rejected');

            return $updated;
        });
    }

    public function blacklist(int $applicantId, string $alasan, int $userId): void
    {
        Gate::authorize('recruitment.applicant.blacklist');

        DB::transaction(function () use ($applicantId, $alasan, $userId): void {
            $applicant = $this->findApplicant($applicantId);

            $this->blacklists->create([
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicant->getKey(),
                'status' => 'active',
                'reason' => $alasan,
                'blacklisted_by' => $userId,
                'blacklisted_at' => now(),
                'created_by' => $userId,
            ]);

            $this->applicants->update($applicant, [
                'is_blacklisted' => true,
                'status' => 'blacklisted',
                'updated_by' => $userId,
            ]);
        });
    }

    public function unblacklist(int $blacklistId, int $userId): void
    {
        Gate::authorize('recruitment.blacklist.manage');

        DB::transaction(function () use ($blacklistId, $userId): void {
            $blacklist = $this->blacklists->findById($blacklistId);

            if ($blacklist === null) {
                throw (new ModelNotFoundException)->setModel(ApplicantBlacklist::class, $blacklistId);
            }

            $this->blacklists->archive($blacklist);

            $applicant = $this->findApplicant((int) $blacklist->getAttribute('applicant_id'));
            $this->applicants->update($applicant, [
                'is_blacklisted' => false,
                'status' => 'active',
                'updated_by' => $userId,
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

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $overrides
     */
    private function createApplicant(array $data, int $companyId, array $overrides): Applicant
    {
        return $this->applicants->create(array_merge($data, [
            'company_id' => $companyId,
            'application_number' => $data['application_number'] ?? 'APP-'.now()->format('YmdHis').'-'.random_int(1000, 9999),
            'phone' => $data['phone'] ?? $data['whatsapp'] ?? null,
            'source' => $data['source'] ?? 'website',
            'consent_at' => $data['consent_at'] ?? now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function passesAutoScreening(array $data): bool
    {
        if (! array_key_exists('screening_passed', $data) || $data['screening_passed'] === null) {
            return true;
        }

        return (bool) $data['screening_passed'];
    }

    private function allowDuplicateApplicant(): bool
    {
        return filter_var($this->settings->get('allow_duplicate_applicant'), FILTER_VALIDATE_BOOLEAN);
    }

    public function isSkillTestRequired(?JobPosting $jobPosting): bool
    {
        $position = $jobPosting?->getRelation('position');
        $code = strtolower((string) $position?->getAttribute('code'));

        return in_array($code, ['staff', 'welding', 'maintenance'], true);
    }

    private function stageValue(Applicant $applicant): string
    {
        $stage = $applicant->getAttribute('stage');

        return $stage instanceof \BackedEnum ? (string) $stage->value : (string) $stage;
    }
}
