<?php

use App\Application\Recruitment\ApplicantService;
use App\Jobs\Recruitment\CreateDraftEmployeeJob;
use App\Jobs\Recruitment\SendApplicationConfirmationEmailJob;
use App\Jobs\Recruitment\SendApplicationDuplicateEmailJob;
use App\Jobs\Recruitment\SendApplicationRejectionEmailJob;
use App\Jobs\Recruitment\SendPemberkasanLinkJob;
use App\Jobs\Recruitment\SendQuizLinkJob;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
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

    $this->position = Position::query()->create([
        'company_id' => $this->company->id,
        'code' => 'STAFF',
        'name' => 'Staff',
    ]);

    $this->jobPosting = JobPosting::query()->create([
        'company_id' => $this->company->id,
        'position_id' => $this->position->id,
        'code' => 'JOB-APP',
        'title' => 'Operator',
        'status' => 'published',
    ]);

    $this->user = applicantServiceUser($this->company, 'applicant_actor', [
        'recruitment.applicant.update_stage',
        'recruitment.applicant.blacklist',
        'recruitment.blacklist.manage',
    ]);

    $this->actingAs($this->user);
});

it('submits blacklisted applicants as rejected without entering pipeline', function () {
    $existing = applicantServiceApplicant($this->company, $this->jobPosting, [
        'email' => 'blocked@example.test',
        'phone' => '628111',
    ]);

    ApplicantBlacklist::query()->create([
        'company_id' => $this->company->id,
        'applicant_id' => $existing->id,
        'status' => 'active',
        'reason' => 'Fraud',
    ]);

    $applicant = app(ApplicantService::class)->submitApplication(applicantServicePayload($this, [
        'email' => 'blocked@example.test',
        'phone' => '628999',
    ]), $this->company->id);

    expect($applicant->stage->value)->toBe('rejected')
        ->and($applicant->is_blacklisted)->toBeTrue();

    Queue::assertPushed(SendApplicationRejectionEmailJob::class);
    Queue::assertPushed(SendApplicationConfirmationEmailJob::class);
});

it('rejects duplicate applications when duplicate setting is disabled', function () {
    applicantServiceApplicant($this->company, $this->jobPosting, ['email' => 'dup@example.test']);

    expect(fn () => app(ApplicantService::class)->submitApplication(applicantServicePayload($this, [
        'email' => 'dup@example.test',
    ]), $this->company->id))->toThrow(InvalidArgumentException::class, 'recruitment.applicant.duplicate_not_allowed');
});

it('allows duplicate applications when enabled and dispatches duplicate email', function () {
    CompanySetting::query()->create([
        'company_id' => $this->company->id,
        'key' => 'allow_duplicate_applicant',
        'value' => 'true',
    ]);
    applicantServiceApplicant($this->company, $this->jobPosting, ['email' => 'dup-allowed@example.test']);

    $applicant = app(ApplicantService::class)->submitApplication(applicantServicePayload($this, [
        'email' => 'dup-allowed@example.test',
    ]), $this->company->id);

    expect($applicant->is_duplicate)->toBeTrue()
        ->and($applicant->stage->value)->toBe('screening');

    Queue::assertPushed(SendApplicationDuplicateEmailJob::class);
});

it('skips null auto-screening criteria and rejects explicit screening failure', function () {
    $service = app(ApplicantService::class);

    $passed = $service->submitApplication(applicantServicePayload($this, [
        'email' => 'screening-pass@example.test',
    ]), $this->company->id);
    $failed = $service->submitApplication(applicantServicePayload($this, [
        'email' => 'screening-fail@example.test',
        'screening_passed' => false,
    ]), $this->company->id);

    expect($passed->stage->value)->toBe('screening')
        ->and($failed->stage->value)->toBe('rejected');
});

it('enforces applicant stage transitions and dispatches stage jobs', function () {
    $service = app(ApplicantService::class);
    $applicant = applicantServiceApplicant($this->company, $this->jobPosting, ['stage' => 'screening']);

    expect(fn () => $service->advanceStage($applicant->id, 'hired', $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.applicant.use_hire_applicant');

    $testStage = $service->advanceStage($applicant->id, 'tes_tulis', $this->user->id);
    $pemberkasan = $service->advanceStage($applicant->id, 'pemberkasan', $this->user->id);
    $hired = $service->hireApplicant($applicant->id, $this->user->id);

    expect($testStage->stage->value)->toBe('tes_tulis')
        ->and($pemberkasan->stage->value)->toBe('pemberkasan')
        ->and($hired->stage->value)->toBe('hired');

    Queue::assertPushed(SendQuizLinkJob::class);
    Queue::assertPushed(SendPemberkasanLinkJob::class);
    Queue::assertPushed(CreateDraftEmployeeJob::class);
});

it('blacklists and unblacklists applicants without hard delete', function () {
    $service = app(ApplicantService::class);
    $applicant = applicantServiceApplicant($this->company, $this->jobPosting);

    $service->blacklist($applicant->id, 'Fraud', $this->user->id);

    $blacklist = ApplicantBlacklist::query()->firstOrFail();

    expect($applicant->refresh()->is_blacklisted)->toBeTrue();

    $service->unblacklist($blacklist->id, $this->user->id);

    expect($applicant->refresh()->is_blacklisted)->toBeFalse()
        ->and(ApplicantBlacklist::withArchived()->findOrFail($blacklist->id)->archived_at)->not->toBeNull();
});

function applicantServicePayload(object $testCase, array $overrides = []): array
{
    return array_merge([
        'job_posting_id' => $testCase->jobPosting->id,
        'name' => 'Applicant',
        'email' => 'applicant-'.uniqid().'@example.test',
        'phone' => '628'.random_int(100000, 999999),
    ], $overrides);
}

function applicantServiceApplicant(Company $company, JobPosting $jobPosting, array $overrides = []): Applicant
{
    return Applicant::query()->create(array_merge([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-'.uniqid(),
        'name' => 'Applicant',
        'email' => 'applicant-'.uniqid().'@example.test',
        'phone' => '628'.random_int(100000, 999999),
        'stage' => 'screening',
        'status' => 'active',
        'consent_at' => now(),
    ], $overrides));
}

/**
 * @param  array<int, string>  $permissionCodes
 */
function applicantServiceUser(Company $company, string $roleCode, array $permissionCodes): User
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

    foreach ($permissionCodes as $code) {
        [$module, $action] = explode('.', $code, 2);

        $permission = Permission::query()->create([
            'company_id' => $company->id,
            'code' => $code,
            'module' => $module,
            'action' => $action,
            'name' => $code,
        ]);

        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}
