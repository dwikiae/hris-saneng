<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores sensitive employee fields encrypted', function () {
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = employeeModelUser($company->id, 'encrypted');

    $employee = Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-2026-0001',
        'full_name' => 'Encrypted Employee',
        'name' => 'Encrypted Employee',
        'nik' => '3374010101010001',
        'npwp' => '09.123.456.7-891.000',
        'bank_account_number' => '1234567890',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::PENDING,
    ]);

    $raw = DB::table('employees')->where('id', $employee->id)->first();

    expect($raw->nik)->not->toBe('3374010101010001')
        ->and($raw->npwp)->not->toBe('09.123.456.7-891.000')
        ->and($raw->bank_account_number)->not->toBe('1234567890')
        ->and($employee->fresh()->nik)->toBe('3374010101010001');
});

it('excludes archived employees through the global scope', function () {
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = employeeModelUser($company->id, 'archive');

    Employee::create(employeeModelPayload($company->id, $user->id, 'EMP-2026-0001'));
    Employee::create(employeeModelPayload($company->id, $user->id, 'EMP-2026-0002', [
        'archived_at' => now(),
    ]));

    expect(Employee::count())->toBe(1)
        ->and(Employee::withArchived()->count())->toBe(2);
});

it('scopes employees to the configured company', function () {
    $saneng = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $other = Company::create(['name' => 'PT Lain', 'legal_name' => 'PT Lain']);
    $sanengUser = employeeModelUser($saneng->id, 'saneng');
    $otherUser = employeeModelUser($other->id, 'other');

    config(['company.default_id' => $saneng->id]);

    Employee::create(employeeModelPayload($saneng->id, $sanengUser->id, 'EMP-2026-0001'));
    Employee::create(employeeModelPayload($other->id, $otherUser->id, 'EMP-2026-0002'));

    expect(Employee::count())->toBe(1)
        ->and(Employee::withoutCompanyScope()->count())->toBe(2);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function employeeModelPayload(int $companyId, int $userId, string $employeeNumber, array $overrides = []): array
{
    return array_merge([
        'company_id' => $companyId,
        'employee_number' => $employeeNumber,
        'full_name' => 'Model Employee',
        'name' => 'Model Employee',
        'nik' => '3374010101010001',
        'consent_at' => now(),
        'consent_by' => $userId,
        'status' => Employee::PENDING,
    ], $overrides);
}

function employeeModelUser(int $companyId, string $suffix): User
{
    return User::create([
        'company_id' => $companyId,
        'name' => 'Employee Model '.$suffix,
        'email' => "employee.model.{$suffix}@saneng.co.id",
        'password' => 'password',
    ]);
}
