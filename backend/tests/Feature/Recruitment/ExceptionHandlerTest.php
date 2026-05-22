<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantBlacklist;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns standard json for unauthenticated private requests', function () {
    $this->getJson('/api/v1/recruitment/tests')
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'error.unauthenticated',
            'errors' => [],
        ]);
});

it('returns standard json for authenticated users without permission', function () {
    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $user = User::query()->create([
        'company_id' => $company->id,
        'name' => 'No Permission',
        'email' => 'no-permission@example.test',
        'password' => 'password',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/recruitment/tests')
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'error.forbidden',
            'errors' => [],
        ]);
});

it('returns recruitment test and blacklist lists from repositories', function () {
    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $user = exceptionHandlerUser($company, [
        'recruitment.test.view',
        'recruitment.blacklist.view',
    ]);

    RecruitmentTest::query()->create([
        'company_id' => $company->id,
        'code' => 'TEST-LIST',
        'name' => 'Test List',
        'duration_minutes' => 30,
        'passing_grade' => '7.00',
    ]);

    $jobPosting = JobPosting::query()->create([
        'company_id' => $company->id,
        'code' => 'JOB-BLACKLIST',
        'title' => 'Blacklist Job',
        'status' => 'published',
    ]);

    $applicant = Applicant::query()->create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-BLACKLIST',
        'name' => 'Blacklisted Applicant',
        'email' => 'blacklisted@example.test',
        'phone' => '628123',
        'stage' => 'rejected',
        'status' => 'blacklisted',
        'consent_at' => now(),
    ]);

    ApplicantBlacklist::query()->create([
        'company_id' => $company->id,
        'applicant_id' => $applicant->id,
        'status' => 'active',
        'reason' => 'Test',
        'blacklisted_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/recruitment/tests')
        ->assertOk()
        ->assertJsonPath('data.0.code', 'TEST-LIST');

    $this->actingAs($user)
        ->getJson('/api/v1/recruitment/blacklist')
        ->assertOk()
        ->assertJsonPath('data.data.0.reason', 'Test');
});

/**
 * @param  array<int, string>  $permissionCodes
 */
function exceptionHandlerUser(Company $company, array $permissionCodes): User
{
    $user = User::query()->create([
        'company_id' => $company->id,
        'name' => 'Recruitment Viewer',
        'email' => 'recruitment-viewer@example.test',
        'password' => 'password',
    ]);

    $role = Role::query()->create([
        'company_id' => $company->id,
        'code' => 'recruitment_viewer',
        'name' => 'Recruitment Viewer',
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
