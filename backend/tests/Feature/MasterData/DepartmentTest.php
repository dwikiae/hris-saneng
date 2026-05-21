<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id' => $company->id,
        'name'       => 'Test User',
        'email'      => 'test@test.com',
        'password'   => 'password',
    ]);
    $this->actingAs($this->user);
});

it('index returns list', function () {
    Department::create(['company_id' => 1, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);
    Department::create(['company_id' => 1, 'code' => 'IT', 'name' => 'IT', 'is_active' => true]);

    $this->getJson('/api/v1/master-data/departments')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('index filters by is_active', function () {
    Department::create(['company_id' => 1, 'code' => 'ACTIVE', 'name' => 'Active Dept', 'is_active' => true]);
    Department::create(['company_id' => 1, 'code' => 'INACTIVE', 'name' => 'Inactive Dept', 'is_active' => false]);

    $response = $this->getJson('/api/v1/master-data/departments?is_active=true');
    $response->assertOk();

    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['code'])->toBe('ACTIVE');
});

it('show returns single record', function () {
    $dept = Department::create(['company_id' => 1, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);

    $this->getJson("/api/v1/master-data/departments/{$dept->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.code', 'HRD');
});

it('show returns 404 for unknown id', function () {
    $this->getJson('/api/v1/master-data/departments/9999')
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('store creates with valid data', function () {
    $this->postJson('/api/v1/master-data/departments', ['code' => 'LOGISTIK', 'name' => 'Logistik'])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'master_data.created')
        ->assertJsonPath('data.code', 'LOGISTIK');

    expect(Department::where('code', 'LOGISTIK')->exists())->toBeTrue();
});

it('store fails with missing code', function () {
    $this->postJson('/api/v1/master-data/departments', ['name' => 'No Code'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('store fails with missing name', function () {
    $this->postJson('/api/v1/master-data/departments', ['code' => 'NONAME'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('store fails with duplicate code', function () {
    Department::create(['company_id' => 1, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);

    $this->postJson('/api/v1/master-data/departments', ['code' => 'HRD', 'name' => 'Another HRD'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('update changes name and is_active', function () {
    $dept = Department::create(['company_id' => 1, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);

    $this->putJson("/api/v1/master-data/departments/{$dept->id}", [
        'name'      => 'Human Resources',
        'is_active' => false,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'master_data.updated')
        ->assertJsonPath('data.name', 'Human Resources')
        ->assertJsonPath('data.is_active', false);
});

it('update cannot change code', function () {
    $dept = Department::create(['company_id' => 1, 'code' => 'ORIGINAL', 'name' => 'Original', 'is_active' => true]);

    $this->putJson("/api/v1/master-data/departments/{$dept->id}", [
        'name' => 'Updated Name',
        'code' => 'CHANGED',
    ])->assertOk();

    $dept->refresh();
    expect($dept->code)->toBe('ORIGINAL')
        ->and($dept->name)->toBe('Updated Name');
});

it('archive soft-archives record', function () {
    $dept = Department::create(['company_id' => 1, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);

    $this->postJson("/api/v1/master-data/departments/{$dept->id}/archive")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'master_data.archived');

    expect(Department::find($dept->id))->toBeNull();

    $dept->refresh();
    expect($dept->archived_at)->not->toBeNull();
});

it('restore brings record back', function () {
    $dept = Department::create(['company_id' => 1, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);
    $dept->archive($this->user->id);

    $this->postJson("/api/v1/master-data/departments/{$dept->id}/restore")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'master_data.restored');

    expect(Department::find($dept->id))->not->toBeNull();
});
