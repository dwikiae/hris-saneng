<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('companies table exists with required columns', function () {
    expect(Schema::hasTable('companies'))->toBeTrue()
        ->and(Schema::hasColumns('companies', [
            'id',
            'name',
            'legal_name',
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
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('users table has company_id, archived_at, archived_by columns', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'company_id',
            'archived_at',
            'archived_by',
        ]))->toBeTrue();
});

it('failed_jobs table exists', function () {
    expect(Schema::hasTable('failed_jobs'))->toBeTrue();
});
