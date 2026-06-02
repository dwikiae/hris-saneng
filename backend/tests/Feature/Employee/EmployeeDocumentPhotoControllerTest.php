<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeePhoto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakePngUpload(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
    );
}

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id' => $this->company->id,
        'name' => 'System Admin',
        'email' => 'admin.documents@saneng.co.id',
        'password' => 'password',
    ]);

    $role = Role::create([
        'company_id' => $this->company->id,
        'code' => 'system_admin',
        'name' => 'System Admin',
    ]);

    $this->user->roles()->attach($role->id);
    $this->actingAs($this->user);

    $this->employee = Employee::create([
        'company_id' => $this->company->id,
        'employee_number' => 'EMP-001',
        'name' => 'Budi Saneng',
        'consent_at' => now(),
        'consent_by' => $this->user->id,
        'status' => Employee::DRAFT,
    ]);
});

it('uploads and lists employee documents with signed url field', function () {
    Storage::fake('documents');

    $file = fakePngUpload('ktp.png');

    $this->postJson("/api/v1/employees/{$this->employee->id}/documents", [
        'doc_type' => 'ktp',
        'document' => $file,
    ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.document_type', 'ktp')
        ->assertJsonPath('data.uploaded_by', $this->user->id)
        ->assertJsonStructure(['data' => ['signed_url']]);

    $document = EmployeeDocument::firstOrFail();

    expect($document->path)->toStartWith("employees/{$this->employee->id}/documents/ktp/");
    expect($document->uploaded_at)->not->toBeNull();
    Storage::disk('documents')->assertExists($document->path);

    $this->getJson("/api/v1/employees/{$this->employee->id}/documents")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', $document->id)
        ->assertJsonStructure(['data' => [['signed_url']]]);
});

it('archives employee documents without deleting stored files', function () {
    Storage::fake('documents');

    $document = EmployeeDocument::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'document_type' => 'contract',
        'original_filename' => 'contract.jpg',
        'storage_disk' => 'documents',
        'path' => "employees/{$this->employee->id}/documents/contract/contract.jpg",
        'mime_type' => 'image/jpeg',
        'size_bytes' => 100,
        'uploaded_by' => $this->user->id,
        'uploaded_at' => now(),
    ]);
    Storage::disk('documents')->put($document->path, 'document-content');

    $this->deleteJson("/api/v1/employees/{$this->employee->id}/documents/{$document->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $archived = EmployeeDocument::withArchived()->findOrFail($document->id);

    expect($archived->archived_at)->not->toBeNull();
    Storage::disk('documents')->assertExists($document->path);
});

it('rejects unsupported employee document mime type', function () {
    Storage::fake('documents');

    $this->postJson("/api/v1/employees/{$this->employee->id}/documents", [
        'doc_type' => 'script',
        'document' => UploadedFile::fake()->create('script.txt', 1, 'text/plain'),
    ])->assertUnprocessable();

    expect(EmployeeDocument::count())->toBe(0);
});

it('uploads employee photo variants and returns their urls', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension is required to generate employee photo variants.');
    }

    Storage::fake('public');

    $this->postJson("/api/v1/employees/{$this->employee->id}/photo", [
        'photo' => UploadedFile::fake()->image('photo.jpg', 1200, 900)->size(512),
    ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['original', 'medium', 'thumbnail']]);

    $photo = EmployeePhoto::firstOrFail();
    $extension = pathinfo($photo->path, PATHINFO_EXTENSION);
    $basePath = substr($photo->path, 0, -strlen('.'.$extension));

    expect($photo->path)->toStartWith("employees/{$this->employee->id}/photo/");
    Storage::disk('public')->assertExists($photo->path);
    Storage::disk('public')->assertExists($basePath.'_medium.'.$extension);
    Storage::disk('public')->assertExists($basePath.'_thumbnail.'.$extension);

    $this->getJson("/api/v1/employees/{$this->employee->id}/photo")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['original', 'medium', 'thumbnail']]);
});
