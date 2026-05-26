<?php

use App\Jobs\Recruitment\CreateDraftEmployeeJob;
use App\Jobs\Recruitment\SendApplicationConfirmationEmailJob;
use App\Jobs\Recruitment\SendApplicationDuplicateEmailJob;
use App\Jobs\Recruitment\SendApplicationRejectionEmailJob;
use App\Jobs\Recruitment\SendInterviewScheduleEmailJob;
use App\Jobs\Recruitment\SendPemberkasanEmailJob;
use App\Jobs\Recruitment\SendQuizEmailJob;
use App\Mail\Recruitment\RecruitmentCandidateMail;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantDocument;
use App\Models\Recruitment\InterviewSchedule;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\QuizSession;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config([
        'app.company_id' => $this->company->id,
        'company.default_id' => $this->company->id,
        'app.url' => 'https://hris.example.test',
    ]);

    CompanySetting::query()->create([
        'company_id' => $this->company->id,
        'key' => 'applicant_data_retention_days',
        'value' => '365',
    ]);

    $this->user = User::query()->create([
        'company_id' => $this->company->id,
        'name' => 'HR Admin',
        'email' => 'hr-admin@example.test',
        'password' => 'password',
    ]);
});

it('marks every recruitment job as queued with retry backoff', function () {
    $jobs = [
        new SendApplicationConfirmationEmailJob(1),
        new SendApplicationDuplicateEmailJob(1),
        new SendApplicationRejectionEmailJob(1),
        new SendQuizEmailJob(1),
        new SendInterviewScheduleEmailJob(1, 'token', '628123'),
        new SendPemberkasanEmailJob(1, 'token'),
        new CreateDraftEmployeeJob(1, 1),
    ];

    foreach ($jobs as $job) {
        expect($job)->toBeInstanceOf(ShouldQueue::class)
            ->and($job->tries)->toBe(3)
            ->and($job->backoff)->toBe([60, 300, 900]);
    }
});

it('sends all recruitment candidate emails through mailables', function () {
    $fixture = recruitmentJobFixture($this->company);

    dispatch_sync(new SendApplicationConfirmationEmailJob($fixture['applicant']->id));
    dispatch_sync(new SendApplicationDuplicateEmailJob($fixture['applicant']->id));
    dispatch_sync(new SendApplicationRejectionEmailJob($fixture['applicant']->id));
    dispatch_sync(new SendQuizEmailJob($fixture['quizSession']->id));
    dispatch_sync(new SendInterviewScheduleEmailJob($fixture['schedule']->id, 'interview-token', '628123456789'));
    dispatch_sync(new SendPemberkasanEmailJob($fixture['document']->id, 'pemberkaasan-token'));

    Mail::assertSent(RecruitmentCandidateMail::class, 6);
    Mail::assertSent(RecruitmentCandidateMail::class, function (RecruitmentCandidateMail $mail) use ($fixture): bool {
        return $mail->hasTo((string) $fixture['applicant']->email);
    });
});

it('renders the recruitment email blade template', function () {
    $html = (new RecruitmentCandidateMail(
        'Konfirmasi Lamaran Diterima',
        'emails.recruitment.application-confirmation',
        [
            'title' => 'Lamaran Anda Sudah Diterima',
            'applicantName' => 'Kandidat',
            'positionTitle' => 'Staff',
            'companyName' => 'PT Saneng',
        ]
    ))->render();

    expect($html)->toContain('Lamaran Anda Sudah Diterima')
        ->and($html)->toContain('Kandidat');
});

it('creates a draft employee from a hired applicant', function () {
    $fixture = recruitmentJobFixture($this->company, ['stage' => 'hired']);

    dispatch_sync(new CreateDraftEmployeeJob($fixture['applicant']->id, $this->user->id));

    $employee = Employee::query()->firstOrFail();

    expect($employee->name)->toBe($fixture['applicant']->name)
        ->and($employee->employee_number)->toBeNull()
        ->and($employee->email)->toBe($fixture['applicant']->email)
        ->and($employee->phone)->toBe($fixture['applicant']->phone)
        ->and($employee->position_id)->toBe($fixture['position']->id)
        ->and($employee->status)->toBe(Employee::PENDING)
        ->and($employee->consent_at)->not->toBeNull()
        ->and($employee->consent_by)->toBe($this->user->id);
});

/**
 * @param  array<string, mixed>  $applicantOverrides
 * @return array<string, mixed>
 */
function recruitmentJobFixture(Company $company, array $applicantOverrides = []): array
{
    $position = Position::query()->create([
        'company_id' => $company->id,
        'code' => 'STAFF-'.uniqid(),
        'name' => 'Staff',
    ]);

    $test = RecruitmentTest::query()->create([
        'company_id' => $company->id,
        'code' => 'TEST-'.uniqid(),
        'name' => 'Tes Staff',
        'duration_minutes' => 45,
        'passing_grade' => '7.00',
    ]);

    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'position_id' => $position->id,
        'test_id' => $test->id,
        'code' => 'JOB-'.uniqid(),
        'title' => 'Staff',
        'status' => 'published',
    ]);

    $applicant = Applicant::query()->create(array_merge([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-'.uniqid(),
        'name' => 'Kandidat Recruitment',
        'email' => 'candidate-'.uniqid().'@example.test',
        'phone' => '628'.random_int(100000, 999999),
        'stage' => 'screening',
        'status' => 'active',
        'consent_at' => now(),
    ], $applicantOverrides));

    $quizSession = QuizSession::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'test_id' => $test->id,
        'token' => 'quiz-token-'.uniqid(),
        'status' => 'pending',
        'timer_snapshot' => 45,
        'passing_grade_snapshot' => '7.00',
        'expires_at' => now()->addDay(),
    ]);

    $schedule = InterviewSchedule::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'job_posting_id' => $jobPosting->id,
        'token' => 'interview-token-'.uniqid(),
        'expires_at' => now()->addDay(),
        'scheduled_at' => now()->addDay(),
        'location' => 'Ruang HR',
    ]);

    $document = ApplicantDocument::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'document_type' => 'ktp',
        'token' => 'pemberkaasan-token-'.uniqid(),
        'expires_at' => now()->addDay(),
    ]);

    return compact('position', 'test', 'jobPosting', 'applicant', 'quizSession', 'schedule', 'document');
}
