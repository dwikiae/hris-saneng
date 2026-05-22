<?php

use App\Application\Recruitment\InterviewService;
use App\Jobs\Recruitment\SendInterviewScheduleEmailJob;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\InterviewSchedule;
use App\Models\Recruitment\JobPosting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $this->company->id, 'company.default_id' => $this->company->id]);

    CompanySetting::query()->create([
        'company_id' => $this->company->id,
        'key' => 'recruitment_link_expires_hours',
        'value' => '24',
    ]);
    CompanySetting::query()->create([
        'company_id' => $this->company->id,
        'key' => 'hr_whatsapp_number',
        'value' => '628123456789',
    ]);

    $this->user = interviewServiceUser($this->company, 'interview_actor');
    $this->actingAs($this->user);
});

it('schedules an interview with a token and queues the email', function () {
    $applicant = interviewServiceApplicant($this->company, 'OPERATOR', 'interview');

    $schedule = app(InterviewService::class)->scheduleInterview($applicant->id, [
        'scheduled_at' => now()->addDay(),
        'location' => 'HR Room',
    ], $this->user->id);

    expect($schedule->token)->not->toBeNull()
        ->and($schedule->expires_at)->not->toBeNull()
        ->and($schedule->confirmation_status)->toBe('pending');

    Queue::assertPushed(SendInterviewScheduleEmailJob::class);
});

it('rejects applicant when candidate confirms not attending', function () {
    $applicant = interviewServiceApplicant($this->company, 'OPERATOR', 'interview');
    $schedule = app(InterviewService::class)->scheduleInterview($applicant->id, [
        'scheduled_at' => now()->addDay(),
    ], $this->user->id);

    app(InterviewService::class)->confirmAttendance((string) $schedule->token, 'tidak_hadir');

    expect($applicant->refresh()->stage->value)->toBe('rejected')
        ->and($applicant->status->value)->toBe('failed');
});

it('throws when confirming with an expired token', function () {
    $applicant = interviewServiceApplicant($this->company, 'OPERATOR', 'interview');
    $schedule = InterviewSchedule::query()->create([
        'company_id' => $this->company->id,
        'applicant_id' => $applicant->id,
        'job_posting_id' => $applicant->job_posting_id,
        'token' => 'expired-interview-token',
        'expires_at' => now()->subMinute(),
        'scheduled_at' => now()->addDay(),
        'created_by' => $this->user->id,
    ]);

    expect(fn () => app(InterviewService::class)->confirmAttendance((string) $schedule->token, 'hadir'))
        ->toThrow(InvalidArgumentException::class, 'recruitment.interview.token_expired');
});

it('advances passed interview to skill test only for required positions', function () {
    $staff = interviewServiceApplicant($this->company, 'STAFF', 'interview');
    $operator = interviewServiceApplicant($this->company, 'OPERATOR', 'interview');

    $staffResult = app(InterviewService::class)->recordInterviewResult($staff->id, true, 'Passed', $this->user->id);
    $operatorResult = app(InterviewService::class)->recordInterviewResult($operator->id, true, 'Passed', $this->user->id);

    expect($staffResult->stage->value)->toBe('tes_kemampuan')
        ->and($operatorResult->stage->value)->toBe('mcu');
});

function interviewServiceApplicant(Company $company, string $positionCode, string $stage): Applicant
{
    $position = Position::query()->create([
        'company_id' => $company->id,
        'code' => $positionCode,
        'name' => $positionCode,
    ]);

    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'position_id' => $position->id,
        'code' => 'JOB-'.$positionCode.'-'.uniqid(),
        'title' => $positionCode,
        'status' => 'published',
    ]);

    return Applicant::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-'.uniqid(),
        'name' => 'Interview Applicant',
        'email' => 'interview-'.uniqid().'@example.test',
        'phone' => '628'.random_int(100000, 999999),
        'stage' => $stage,
        'status' => 'active',
        'consent_at' => now(),
    ]);
}

function interviewServiceUser(Company $company, string $roleCode): User
{
    $user = User::query()->create([
        'company_id' => $company->id,
        'name' => $roleCode,
        'email' => $roleCode.'@example.test',
        'password' => 'password',
    ]);

    $role = Role::query()->create([
        'company_id' => $company->id,
        'code' => $roleCode,
        'name' => $roleCode,
    ]);

    $permission = Permission::query()->create([
        'company_id' => $company->id,
        'code' => 'recruitment.applicant.update_stage',
        'module' => 'recruitment',
        'action' => 'applicant.update_stage',
        'name' => 'recruitment.applicant.update_stage',
    ]);

    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    return $user;
}
