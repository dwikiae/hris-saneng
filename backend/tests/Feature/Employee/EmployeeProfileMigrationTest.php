<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('adds employee profile columns to employees table', function () {
    expect(Schema::hasColumns('employees', [
        'nickname',
        'religion_id',
        'marital_status_id',
        'blood_type_id',
        'nationality',
        'passport_number',
        'province_id',
        'city_id',
        'domicile_address',
        'domicile_province_id',
        'domicile_city_id',
        'country_of_birth',
        'employee_level_id',
        'work_location_id',
        'supervisor_id',
        'probation_end_date',
    ]))->toBeTrue();
});

it('encrypts passport number through employee cast', function () {
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Passport Consent User',
        'email' => 'passport.consent@saneng.co.id',
        'password' => 'password',
    ]);

    $employee = Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-PASSPORT-001',
        'name' => 'Passport Employee',
        'passport_number' => 'A1234567',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::DRAFT,
    ]);

    $storedValue = DB::table('employees')
        ->where('id', $employee->id)
        ->value('passport_number');

    expect($employee->refresh()->passport_number)->toBe('A1234567');
    expect($storedValue)->not->toBe('A1234567');
});
