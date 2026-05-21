<?php

use App\Models\Company;
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
    $this->getJson('/api/v1/master-data/banks')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('store creates with valid data', function () {
    $this->postJson('/api/v1/master-data/banks', ['code' => 'BCA', 'name' => 'BCA'])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.code', 'BCA');
});
