<?php

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\User;
use App\Services\ArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id' => $this->company->id,
        'name' => 'Admin',
        'email' => 'admin@saneng.co.id',
        'password' => 'password',
    ]);
    $this->actingAs($this->user);
});

it('archives and restores archivable models', function () {
    $service = app(ArchiveService::class);
    $department = Department::create([
        'company_id' => $this->company->id,
        'code' => 'HRD',
        'name' => 'HRD',
    ]);

    $service->archive($department);

    $archived = Department::withArchived()->findOrFail($department->id);
    expect($archived->archived_at)->not->toBeNull();
    expect($archived->archived_by)->toBe($this->user->id);

    $records = $service->getArchivedRecords(Department::class, 10);
    expect($records->total())->toBe(1);

    $service->restore($archived);

    $restored = Department::findOrFail($department->id);
    expect($restored->archived_at)->toBeNull();
    expect($restored->archived_by)->toBeNull();
    expect(DB::table('activity_log')->where('event', 'restored')->where('subject_id', $department->id)->exists())->toBeTrue();
});

it('rejects non archivable models', function () {
    $service = app(ArchiveService::class);
    $setting = CompanySetting::create([
        'company_id' => $this->company->id,
        'key' => 'smtp.host',
        'value' => 'smtp.example.test',
    ]);

    expect(fn () => $service->archive($setting))->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->getArchivedRecords(CompanySetting::class, 10))->toThrow(InvalidArgumentException::class);
});
