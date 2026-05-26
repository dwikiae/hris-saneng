<?php

use App\Application\Employee\SendChatterMessageService;
use App\Application\Employee\UploadEmployeeDocumentService;
use App\Application\Employee\UploadEmployeePhotoService;
use App\Jobs\Employee\ProcessEmployeePhotoJob;
use App\Jobs\Employee\SendChatterMentionNotificationJob;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('sends chatter message with mention records and notifications', function () {
    Queue::fake();
    $fixture = chatterFileFixture(['chatter.create']);
    $mentioned = chatterFileUser($fixture['company'], 'Mentioned_User', []);

    $this->actingAs($fixture['actor']);

    $message = app(SendChatterMessageService::class)->execute($fixture['employee'], 'Halo @Mentioned_User');

    expect($message->mentions()->where('mentioned_user_id', $mentioned->id)->exists())->toBeTrue();
    Queue::assertPushed(SendChatterMentionNotificationJob::class);
});

it('uploads employee document to documents disk', function () {
    Storage::fake('documents');
    $fixture = chatterFileFixture(['employee.update']);
    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    $this->actingAs($fixture['actor']);

    $document = app(UploadEmployeeDocumentService::class)->execute($fixture['employee'], 'kontrak', $file);

    Storage::disk('documents')->assertExists($document->path);
    expect($document->document_type)->toBe('kontrak')
        ->and($fixture['employee']->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();
});

it('rejects document larger than ten megabytes', function () {
    $fixture = chatterFileFixture(['employee.update']);
    $file = UploadedFile::fake()->create('oversized.pdf', 11 * 1024, 'application/pdf');

    $this->actingAs($fixture['actor']);

    expect(fn () => app(UploadEmployeeDocumentService::class)->execute($fixture['employee'], 'kontrak', $file))
        ->toThrow(ValidationException::class);
});

it('uploads employee photo and dispatches processing job', function () {
    Queue::fake();
    Storage::fake('public');
    $fixture = chatterFileFixture(['employee.update']);
    $file = UploadedFile::fake()->create('avatar.png', 100, 'image/png');

    $this->actingAs($fixture['actor']);

    $photo = app(UploadEmployeePhotoService::class)->execute($fixture['employee'], $file);

    Storage::disk('public')->assertExists($photo->path);
    Queue::assertPushed(ProcessEmployeePhotoJob::class);
});

it('rejects photo larger than two megabytes', function () {
    $fixture = chatterFileFixture(['employee.update']);
    $file = UploadedFile::fake()->create('oversized.png', 3 * 1024, 'image/png');

    $this->actingAs($fixture['actor']);

    expect(fn () => app(UploadEmployeePhotoService::class)->execute($fixture['employee'], $file))
        ->toThrow(ValidationException::class);
});

/**
 * @param  list<string>  $permissions
 * @return array<string, mixed>
 */
function chatterFileFixture(array $permissions): array
{
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $actor = chatterFileUser($company, 'chatter_file_actor', $permissions);
    $department = Department::create(['company_id' => $company->id, 'code' => 'HRD', 'name' => 'HRD']);
    $position = Position::create(['company_id' => $company->id, 'code' => 'STAFF', 'name' => 'Staff']);
    $employmentType = EmploymentType::create(['company_id' => $company->id, 'code' => 'PKWT', 'name' => 'PKWT']);
    $employee = Employee::create([
        'company_id' => $company->id,
        'full_name' => 'File Employee',
        'name' => 'File Employee',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'employment_type_id' => $employmentType->id,
        'nik' => '3374010101010001',
        'consent_at' => now(),
        'consent_by' => $actor->id,
        'consent_text' => 'Consent text',
        'status' => Employee::ACTIVE,
    ]);

    return compact('company', 'actor', 'employee');
}

/**
 * @param  list<string>  $permissions
 */
function chatterFileUser(Company $company, string $roleCode, array $permissions): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => $roleCode,
        'email' => $roleCode.'@saneng.co.id',
        'password' => 'password',
    ]);
    $role = Role::create(['company_id' => $company->id, 'code' => $roleCode, 'name' => $roleCode]);

    foreach ($permissions as $code) {
        [$module, $action] = explode('.', $code, 2);
        $permission = Permission::create([
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
