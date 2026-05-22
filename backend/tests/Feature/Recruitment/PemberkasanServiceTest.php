<?php

use App\Application\Recruitment\PemberkasanService;
use App\Jobs\Recruitment\CreateDraftEmployeeJob;
use App\Jobs\Recruitment\SendPemberkasanEmailJob;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
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

    $this->user = pemberkasanServiceUser($this->company, 'pemberkaasan_actor');
    $this->actingAs($this->user);
});

it('sends a pemberkaasan portal token and queues the email', function () {
    $applicant = pemberkasanServiceApplicant($this->company, 'pemberkasan');

    $document = app(PemberkasanService::class)->sendPemberkasanLink($applicant->id, $this->user->id);

    expect($document->token)->not->toBeNull()
        ->and($document->expires_at)->not->toBeNull()
        ->and($applicant->documents()->count())->toBe(6);

    Queue::assertPushed(SendPemberkasanEmailJob::class);
});

it('rejects active resend attempts and accepts expired tokens for resend', function () {
    $applicant = pemberkasanServiceApplicant($this->company, 'pemberkasan');
    $document = app(PemberkasanService::class)->sendPemberkasanLink($applicant->id, $this->user->id);

    expect(fn () => app(PemberkasanService::class)->resendPemberkasanLink($applicant->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.pemberkaasan.link_still_active');

    $applicant->documents()->update(['expires_at' => now()->subMinute()]);

    $newDocument = app(PemberkasanService::class)->resendPemberkasanLink($applicant->id, $this->user->id);

    expect($newDocument->token)->not->toBe($document->token);
});

it('returns portal data and records uploaded document path', function () {
    $applicant = pemberkasanServiceApplicant($this->company, 'pemberkasan');
    $document = app(PemberkasanService::class)->sendPemberkasanLink($applicant->id, $this->user->id);

    $uploaded = app(PemberkasanService::class)->uploadDocument(
        (string) $document->token,
        'ktp',
        'recruitment/1/applicants/1/pemberkasan/ktp/file.pdf',
        $this->user->id
    );
    $portalData = app(PemberkasanService::class)->getPortalData((string) $document->token);

    expect($uploaded->path)->toBe('recruitment/1/applicants/1/pemberkasan/ktp/file.pdf')
        ->and($uploaded->uploaded_at)->not->toBeNull()
        ->and($portalData['documents'])->toHaveCount(6);
});

it('guards hired transition so it only starts from pemberkasan', function () {
    $screening = pemberkasanServiceApplicant($this->company, 'screening');
    $pemberkasan = pemberkasanServiceApplicant($this->company, 'pemberkasan');

    expect(fn () => app(PemberkasanService::class)->verifyAndHire($screening->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.pemberkaasan.requires_pemberkasan_stage');

    $hired = app(PemberkasanService::class)->verifyAndHire($pemberkasan->id, $this->user->id);

    expect($hired->stage->value)->toBe('hired');
    Queue::assertPushed(CreateDraftEmployeeJob::class);
});

function pemberkasanServiceApplicant(Company $company, string $stage): Applicant
{
    $position = Position::query()->firstOrCreate(
        ['company_id' => $company->id, 'code' => 'OPERATOR'],
        ['name' => 'Operator']
    );

    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'position_id' => $position->id,
        'code' => 'JOB-PEMBERKAASAN-'.uniqid(),
        'title' => 'Operator',
        'status' => 'published',
    ]);

    return Applicant::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-'.uniqid(),
        'name' => 'Pemberkasan Applicant',
        'email' => 'pemberkaasan-'.uniqid().'@example.test',
        'phone' => '628'.random_int(100000, 999999),
        'stage' => $stage,
        'status' => 'active',
        'consent_at' => now(),
    ]);
}

function pemberkasanServiceUser(Company $company, string $roleCode): User
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
