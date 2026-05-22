<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\InterviewSchedule;
use App\Repositories\Contracts\Recruitment\InterviewScheduleRepositoryInterface;

class InterviewScheduleRepository implements InterviewScheduleRepositoryInterface
{
    public function __construct(private readonly InterviewSchedule $model) {}

    public function findByToken(string $token): ?InterviewSchedule
    {
        /** @var InterviewSchedule|null $schedule */
        $schedule = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('token', $token)
            ->first();

        return $schedule;
    }

    public function findLatestByApplicant(int $applicantId): ?InterviewSchedule
    {
        /** @var InterviewSchedule|null $schedule */
        $schedule = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('applicant_id', $applicantId)
            ->orderByDesc('scheduled_at')
            ->first();

        return $schedule;
    }

    public function create(array $data): InterviewSchedule
    {
        /** @var InterviewSchedule $schedule */
        $schedule = $this->model->newQuery()->create($data);

        return $schedule;
    }

    public function replaceForApplicant(int $applicantId, array $data): InterviewSchedule
    {
        $schedule = $this->findLatestByApplicant($applicantId);

        if ($schedule instanceof InterviewSchedule) {
            return $this->update($schedule, $data);
        }

        return $this->create($data);
    }

    public function update(InterviewSchedule $schedule, array $data): InterviewSchedule
    {
        $schedule->update($data);

        return $schedule->refresh();
    }

    public function updateConfirmation(InterviewSchedule $schedule, string $status): InterviewSchedule
    {
        $data = [
            'confirmation_status' => $status,
            'confirmed_at' => now(),
        ];

        if (in_array($status, ['scheduled', 'completed', 'cancelled', 'no_show'], true)) {
            $data['status'] = $status;
        }

        return $this->update($schedule, $data);
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
