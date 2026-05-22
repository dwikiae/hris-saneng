<?php

use App\Application\Recruitment\TestService;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\Recruitment\TestQuestion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $this->company->id, 'company.default_id' => $this->company->id]);

    $this->user = recruitmentServiceUser($this->company, 'test_actor', [
        'recruitment.test.create',
        'recruitment.test.update',
    ]);

    $this->actingAs($this->user);
});

it('creates and updates tests with permissions', function () {
    $service = app(TestService::class);

    $test = $service->create([
        'code' => 'TEST-A3-001',
        'name' => 'Ability Test',
        'passing_grade' => '75.00',
    ], $this->user->id);

    $updated = $service->update($test->id, ['name' => 'Updated Ability Test'], $this->user->id);

    expect($test->created_by)->toBe($this->user->id)
        ->and($updated->name)->toBe('Updated Ability Test')
        ->and($updated->updated_by)->toBe($this->user->id);
});

it('adds updates and archives questions when parent test is unlocked', function () {
    $service = app(TestService::class);
    $test = testServiceTest($this->company);

    $question = $service->addQuestion($test->id, [
        'type' => 'multiple_choice',
        'question' => 'Question one',
        'pilihan' => ['A', 'B'],
        'answer_key' => 'A',
        'bobot' => '1.00',
    ], $this->user->id);

    $updated = $service->updateQuestion($question->id, ['question' => 'Updated question'], $this->user->id);
    $service->deleteQuestion($updated->id, $this->user->id);

    expect($question->created_by)->toBe($this->user->id)
        ->and($updated->question)->toBe('Updated question')
        ->and(TestQuestion::withArchived()->findOrFail($question->id)->archived_at)->not->toBeNull();
});

it('rejects test and question changes when locked by published posting', function () {
    $service = app(TestService::class);
    $test = testServiceTest($this->company);
    $question = TestQuestion::query()->create([
        'company_id' => $this->company->id,
        'test_id' => $test->id,
        'type' => 'multiple_choice',
        'question' => 'Locked question',
        'pilihan' => ['A', 'B'],
        'answer_key' => 'A',
    ]);

    JobPosting::query()->create([
        'company_id' => $this->company->id,
        'test_id' => $test->id,
        'code' => 'JOB-LOCKED',
        'title' => 'Locked Job',
        'status' => 'published',
    ]);

    expect(fn () => $service->update($test->id, ['name' => 'Blocked'], $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.test.locked_by_published_posting')
        ->and(fn () => $service->updateQuestion($question->id, ['question' => 'Blocked'], $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.test.locked_by_published_posting')
        ->and(fn () => $service->deleteQuestion($question->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.test.locked_by_published_posting')
        ->and(fn () => $service->archive($test->id, $this->user->id))
        ->toThrow(InvalidArgumentException::class, 'recruitment.test.locked_by_published_posting');
});

it('archives unlocked tests', function () {
    $service = app(TestService::class);
    $test = testServiceTest($this->company);

    $archived = $service->archive($test->id, $this->user->id);

    expect($archived->archived_at)->not->toBeNull()
        ->and(RecruitmentTest::query()->find($test->id))->toBeNull();
});

it('requires create permission', function () {
    $actor = recruitmentServiceUser($this->company, 'test_viewer', []);
    $this->actingAs($actor);

    expect(fn () => app(TestService::class)->create([
        'code' => 'NOAUTH',
        'name' => 'No Auth',
    ], $actor->id))->toThrow(AuthorizationException::class);
});

function testServiceTest(Company $company): RecruitmentTest
{
    return RecruitmentTest::query()->create([
        'company_id' => $company->id,
        'code' => 'TEST-'.uniqid(),
        'name' => 'Ability Test',
    ]);
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
