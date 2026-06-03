<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can create a company model', function () {
    $company = Company::create([
        'name' => 'PT Saneng',
        'legal_name' => 'PT Saneng',
        'timezone' => 'Asia/Jakarta',
        'date_format' => 'DD/MM/YYYY',
        'language_default' => 'id',
    ]);

    expect($company->exists)->toBeTrue()
        ->and($company->name)->toBe('PT Saneng');
});

it('has the expected fillable fields', function () {
    expect((new Company)->getFillable())->toBe([
        'name',
        'legal_name',
        'slug',
        'npwp',
        'address',
        'city',
        'phone',
        'email',
        'logo_path',
        'website',
        'timezone',
        'date_format',
        'language_default',
        'archived_at',
        'archived_by',
    ]);
});
