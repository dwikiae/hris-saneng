<?php

use App\Application\Recruitment\JobPostingService;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $this->company->id, 'company.default_id' => $this->company->id]);

    $this->test = RecruitmentTest::query()->create([
        'company_id' => $this->company->id,
        'code' => 'TEST-JOB',
        'name' => 'Job Test',
    ]);

    $this->user = recruitmentServiceUser($this->company, 'job_actor', [
        'recruitment.job_posting.publish',
        'recruitment.job_posting.archive',
    ]);

    $this->actingAs($this->user);
});

it('creates and updates draft job postings', function () {
    $service = app(JobPostingService::class);

    $jobPosting = $service->create([
        'code' => 'JOB-A3-001',
        'title' => 'Operator',
        'test_id' => $this->test->id,
    ], $this->user->id);

    $updated = $service->update($jobPosting->id, ['title' => 'Operator Produksi'], $this->user->id);

    expect($jobPosting->created_by)->toBe($this->user->id)
        ->and($jobPosting->status->value)->toBe('draft')
        ->and($updated->title)->toBe('Operator Produksi')
        ->and($updated->updated_by)->toBe($this->user->id);
});

it('publishes and unpublishes only through valid status transitions', function () {
    $service = app(JobPostingService::class);
    $jobPosting = jobPostingServicePosting($this->company, $this->test, ['status' => 'draft']);

    $published = $service->publish($jobPosting->id, $this->user->id);

    expect($published->status->value)->toBe('published')
        ->and($published->published_at)->not->toBeNull();

    $draft = $service->unpublish($published->id, $this->user->id);

    expect($draft->status->value)->toBe('draft');

    $service->publish($draft->id, $this->user->id);

    expect(fn () => $service->publish($draft->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.job_posting.only_draft_can_be_published');
});

it('rejects publishing without a test and updating published postings', function () {
    $service = app(JobPostingService::class);
    $withoutTest = jobPostingServicePosting($this->company, null, ['status' => 'draft']);
    $published = jobPostingServicePosting($this->company, $this->test, ['status' => 'published']);

    expect(fn () => $service->publish($withoutTest->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.job_posting.test_required_before_publish')
        ->and(fn () => $service->update($published->id, ['title' => 'Blocked'], $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.job_posting.published_cannot_be_updated');
});

it('archives draft postings without hard deleting archived records', function () {
    $service = app(JobPostingService::class);
    $jobPosting = jobPostingServicePosting($this->company, $this->test, ['status' => 'draft']);

    $archived = $service->archive($jobPosting->id, $this->user->id);

    expect($archived->archived_at)->not->toBeNull()
        ->and(JobPosting::query()->find($jobPosting->id))->toBeNull();

    $service->hardDelete($jobPosting->id, $this->user->id);

    expect(JobPosting::withArchived()->find($jobPosting->id))->not->toBeNull()
        ->and(DB::table('activity_log')->where('event', 'hard_deleted')->exists())->toBeTrue();
});

it('expires overdue published postings back to draft', function () {
    $service = app(JobPostingService::class);
    $jobPosting = jobPostingServicePosting($this->company, $this->test, [
        'status' => 'published',
        'expired_at' => now()->subMinute(),
    ]);

    $service->expireOverdue();

    expect($jobPosting->refresh()->status->value)->toBe('draft');
});

it('requires publish permission', function () {
    $actor = recruitmentServiceUser($this->company, 'job_viewer', []);
    $this->actingAs($actor);

    $jobPosting = jobPostingServicePosting($this->company, $this->test, ['status' => 'draft']);

    expect(fn () => app(JobPostingService::class)->publish($jobPosting->id, $actor->id))
        ->toThrow(AuthorizationException::class);
});

function jobPostingServicePosting(Company $company, ?RecruitmentTest $test, array $overrides = []): JobPosting
{
    return JobPosting::query()->create(array_merge([
        'company_id' => $company->id,
        'test_id' => $test?->id,
        'code' => 'JOB-'.uniqid(),
        'title' => 'Operator',
        'status' => 'draft',
    ], $overrides));
}

if (! function_exists('recruitmentServiceUser')) {
    /**
     * @param  array<int, string>  $permissionCodes
     */
    function recruitmentServiceUser(Company $company, string $roleCode, array $permissionCodes): User
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
}
