<?php

namespace App\Application\Recruitment;

use App\Jobs\Recruitment\SendInterviewScheduleEmailJob;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\InterviewSchedule;
use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\Recruitment\InterviewScheduleRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InterviewService
{
    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly InterviewScheduleRepositoryInterface $schedules,
        private readonly SettingsRepositoryInterface $settings,
        private readonly ApplicantService $applicantService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleInterview(int $applicantId, array $data, int $userId): InterviewSchedule
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return DB::transaction(function () use ($applicantId, $data, $userId): InterviewSchedule {
            $applicant = $this->findApplicant($applicantId);
            $jobPosting = $applicant->getRelation('jobPosting');
            $token = (string) Str::uuid();

            $schedule = $this->schedules->replaceForApplicant($applicantId, [
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicantId,
                'job_posting_id' => $jobPosting instanceof JobPosting ? $jobPosting->getKey() : $applicant->getAttribute('job_posting_id'),
                'interviewer_id' => $data['interviewer_id'] ?? null,
                'token' => $token,
                'expires_at' => now()->addHours($this->linkExpiresHours()),
                'scheduled_at' => $data['scheduled_at'] ?? $data['tanggal_interview'],
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'location' => $data['location'] ?? $data['lokasi_atau_platform'] ?? null,
                'confirmation_status' => 'pending',
                'confirmed_at' => null,
                'status' => 'scheduled',
                'result_notes' => $data['notes'] ?? $data['catatan'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            SendInterviewScheduleEmailJob::dispatch(
                (int) $schedule->getKey(),
                $token,
                $this->hrWhatsappNumber()
            );

            return $schedule;
        });
    }

    public function resendInterviewLink(int $applicantId, int $userId): InterviewSchedule
    {
        Gate::authorize('recruitment.applicant.update_stage');

        return DB::transaction(function () use ($applicantId, $userId): InterviewSchedule {
            $schedule = $this->schedules->findLatestByApplicant($applicantId);

            if (! $schedule instanceof InterviewSchedule) {
                throw (new ModelNotFoundException)->setModel(InterviewSchedule::class);
            }

            if (! $this->isExpired($schedule)) {
                throw new InvalidArgumentException('recruitment.interview.link_still_active');
            }

            $token = (string) Str::uuid();
            $schedule = $this->schedules->update($schedule, [
                'token' => $token,
                'expires_at' => now()->addHours($this->linkExpiresHours()),
                'confirmation_status' => 'pending',
                'confirmed_at' => null,
                'updated_by' => $userId,
            ]);

            SendInterviewScheduleEmailJob::dispatch(
                (int) $schedule->getKey(),
                $token,
                $this->hrWhatsappNumber()
            );

            return $schedule;
        });
    }

    public function confirmAttendance(string $token, string $status): InterviewSchedule
    {
        return DB::transaction(function () use ($token, $status): InterviewSchedule {
            if (! in_array($status, ['hadir', 'tidak_hadir'], true)) {
                throw new InvalidArgumentException('recruitment.interview.invalid_confirmation_status');
            }

            $schedule = $this->findScheduleByToken($token);
            $this->ensureUsableToken($schedule);

            $schedule = $this->schedules->updateConfirmation($schedule, $status);

            if ($status === 'tidak_hadir') {
                $this->applicantService->reject(
                    (int) $schedule->getAttribute('applicant_id'),
                    (int) $schedule->getAttribute('created_by')
                );
            }

            return $schedule;
        });
    }

    public function recordInterviewResult(int $applicantId, bool $passed, ?string $notes, int $userId): Applicant
    {
        Gate::authorize('recruitment.applicant.update_stage');

        if (! $passed) {
            return $this->applicantService->reject($applicantId, $userId);
        }

        $applicant = $this->findApplicant($applicantId);
        $jobPosting = $applicant->getRelation('jobPosting');
        $nextStage = $this->applicantService->isSkillTestRequired($jobPosting instanceof JobPosting ? $jobPosting : null)
            ? 'tes_kemampuan'
            : 'mcu';

        return $this->applicantService->advanceStage($applicantId, $nextStage, $userId, [
            'interview_notes' => $notes,
        ]);
    }

    private function findApplicant(int $applicantId): Applicant
    {
        $applicant = $this->applicants->findByIdWithPipelineRelations($applicantId);

        if ($applicant === null) {
            throw (new ModelNotFoundException)->setModel(Applicant::class, $applicantId);
        }

        return $applicant;
    }

    private function findScheduleByToken(string $token): InterviewSchedule
    {
        $schedule = $this->schedules->findByToken($token);

        if ($schedule === null) {
            throw (new ModelNotFoundException)->setModel(InterviewSchedule::class);
        }

        return $schedule;
    }

    private function ensureUsableToken(InterviewSchedule $schedule): void
    {
        if ($this->isExpired($schedule)) {
            throw new InvalidArgumentException('recruitment.interview.token_expired');
        }
    }

    private function isExpired(InterviewSchedule $schedule): bool
    {
        $expiresAt = $schedule->getAttribute('expires_at');

        return $expiresAt !== null && $expiresAt->isPast();
    }

    private function linkExpiresHours(): int
    {
        return max(1, (int) ($this->settings->get('recruitment_link_expires_hours') ?? 72));
    }

    private function hrWhatsappNumber(): ?string
    {
        $number = $this->settings->get('hr_whatsapp_number');

        return $number === null ? null : (string) $number;
    }
}
