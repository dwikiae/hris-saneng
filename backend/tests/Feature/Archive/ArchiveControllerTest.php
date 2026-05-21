<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id' => $this->company->id,
        'name' => 'Archive Manager',
        'email' => 'archive.manager@saneng.co.id',
        'password' => 'password',
    ]);
});

function grantArchivePermission(User $user): void
{
    $role = Role::create([
        'company_id' => $user->company_id,
        'code' => 'archive_manager',
        'name' => 'Archive Manager',
    ]);

    $permission = Permission::create([
        'company_id' => $user->company_id,
        'code' => 'archive.manage',
        'module' => 'archive',
        'action' => 'manage',
        'name' => 'Manage Archive',
    ]);

    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);
}

it('lists archived records with filters', function () {
    grantArchivePermission($this->user);
    $this->actingAs($this->user);
    $service = app(ArchiveService::class);
    $department = Department::create([
        'company_id' => $this->company->id,
        'code' => 'HRD',
        'name' => 'HRD',
    ]);

    $service->archive($department);

    $this->actingAs($this->user)
        ->getJson('/api/v1/archive?model=departments&archived_by='.$this->user->id.'&date_from='.now()->toDateString())
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'archive.list')
        ->assertJsonPath('data.data.0.model', 'departments')
        ->assertJsonPath('data.data.0.id', $department->id)
        ->assertJsonPath('data.data.0.archived_by', $this->user->id);
});

it('filters archived records by archived by and date range', function () {
    grantArchivePermission($this->user);
    $this->actingAs($this->user);
    $service = app(ArchiveService::class);
    $included = Department::create([
        'company_id' => $this->company->id,
        'code' => 'INC',
        'name' => 'Included',
    ]);
    $beforeRange = Department::create([
        'company_id' => $this->company->id,
        'code' => 'OLD',
        'name' => 'Old',
    ]);
    $afterRange = Department::create([
        'company_id' => $this->company->id,
        'code' => 'NEW',
        'name' => 'New',
    ]);

    $service->archive($included);
    $service->archive($beforeRange);
    $service->archive($afterRange);

    Department::withArchived()->whereKey($included->id)->update(['archived_at' => '2026-05-15 10:00:00']);
    Department::withArchived()->whereKey($beforeRange->id)->update(['archived_at' => '2026-05-01 10:00:00']);
    Department::withArchived()->whereKey($afterRange->id)->update(['archived_at' => '2026-05-30 10:00:00']);

    $this->actingAs($this->user)
        ->getJson('/api/v1/archive?model=departments&archived_by='.$this->user->id.'&date_from=2026-05-10&date_to=2026-05-20')
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $included->id)
        ->assertJsonPath('data.data.0.model', 'departments');
});

it('lists archived records across all supported models', function () {
    grantArchivePermission($this->user);
    $this->actingAs($this->user);
    $service = app(ArchiveService::class);
    $department = Department::create([
        'company_id' => $this->company->id,
        'code' => 'OPS',
        'name' => 'Operations',
    ]);

    $service->archive($department);

    $this->actingAs($this->user)
        ->getJson('/api/v1/archive')
        ->assertOk()
        ->assertJsonPath('data.data.0.model', 'departments');
});

it('restores archived records', function () {
    grantArchivePermission($this->user);
    $this->actingAs($this->user);
    $service = app(ArchiveService::class);
    $department = Department::create([
        'company_id' => $this->company->id,
        'code' => 'FIN',
        'name' => 'Finance',
    ]);
    $service->archive($department);

    $this->actingAs($this->user)
        ->postJson("/api/v1/archive/departments/{$department->id}/restore")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'archive.restored')
        ->assertJsonPath('data.archived_at', null);

    expect(Department::find($department->id))->not->toBeNull();
});

it('returns not found when restoring a non archived record', function () {
    grantArchivePermission($this->user);
    $department = Department::create([
        'company_id' => $this->company->id,
        'code' => 'LEGAL',
        'name' => 'Legal',
    ]);

    $this->actingAs($this->user)
        ->postJson("/api/v1/archive/departments/{$department->id}/restore")
        ->assertNotFound()
        ->assertJsonPath('message', 'archive.not_found');
});

it('rejects unsupported model aliases when restoring', function () {
    grantArchivePermission($this->user);

    $this->actingAs($this->user)
        ->postJson('/api/v1/archive/company-settings/1/restore')
        ->assertUnprocessable()
        ->assertJsonPath('message', 'archive.model_not_archivable');
});

it('requires archive manage permission', function () {
    $this->actingAs($this->user)
        ->getJson('/api/v1/archive')
        ->assertForbidden();
});

it('rejects unsupported model aliases', function () {
    grantArchivePermission($this->user);

    $this->actingAs($this->user)
        ->getJson('/api/v1/archive?model=company-settings')
        ->assertUnprocessable()
        ->assertJsonPath('message', 'archive.model_not_archivable');
});
