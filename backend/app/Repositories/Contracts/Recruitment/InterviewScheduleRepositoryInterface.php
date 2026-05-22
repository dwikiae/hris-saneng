<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\InterviewSchedule;

interface InterviewScheduleRepositoryInterface
{
    public function findByToken(string $token): ?InterviewSchedule;

    public function findLatestByApplicant(int $applicantId): ?InterviewSchedule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InterviewSchedule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function replaceForApplicant(int $applicantId, array $data): InterviewSchedule;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InterviewSchedule $schedule, array $data): InterviewSchedule;

    public function updateConfirmation(InterviewSchedule $schedule, string $status): InterviewSchedule;
}
