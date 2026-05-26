<?php

use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\OffboardingTemplateSeeder;
use Database\Seeders\TerminationReasonSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates employee module foundation tables', function () {
    $tables = [
        'employees',
        'employee_photos',
        'employee_documents',
        'employee_educations',
        'employee_experiences',
        'employee_family_members',
        'contracts',
        'termination_reasons',
        'offboarding_templates',
        'offboarding_template_items',
        'employee_offboarding',
        'employee_offboarding_items',
        'employee_chatter_messages',
        'employee_chatter_mentions',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
    }
});

it('adds sprint six employee profile columns', function () {
    expect(Schema::hasColumns('employees', [
        'company_id',
        'full_name',
        'nickname',
        'date_of_birth',
        'division_id',
        'unit_id',
        'job_level_id',
        'work_location_id',
        'termination_reason_id',
        'address_ktp',
        'bank_account_name',
        'consent_text',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ]))->toBeTrue();
});

it('enforces one active contract per employee', function () {
    $context = employeeMigrationContext();

    Contract::create($context['contract'] + [
        'contract_number' => 'CTR-2026-0001',
        'status' => Contract::ACTIVE,
    ]);

    expect(fn () => Contract::create($context['contract'] + [
        'contract_number' => 'CTR-2026-0002',
        'status' => Contract::ACTIVE,
    ]))->toThrow(QueryException::class);
});

it('seeds termination reasons contract types and offboarding templates', function () {
    Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);

    $this->seed([
        ContractTypeSeeder::class,
        TerminationReasonSeeder::class,
        OffboardingTemplateSeeder::class,
    ]);

    expect(DB::table('contract_types')->count())->toBe(4)
        ->and(DB::table('termination_reasons')->count())->toBe(6)
        ->and(DB::table('offboarding_templates')->count())->toBe(6)
        ->and(DB::table('offboarding_template_items')->count())->toBeGreaterThan(0);
});

/**
 * @return array<string, array<string, mixed>>
 */
function employeeMigrationContext(): array
{
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'HR Admin',
        'email' => 'hr.admin.migration@saneng.co.id',
        'password' => 'password',
    ]);
    $department = Department::create([
        'company_id' => $company->id,
        'code' => 'HRD',
        'name' => 'HRD',
    ]);
    $position = Position::create([
        'company_id' => $company->id,
        'code' => 'STAFF',
        'name' => 'Staff',
    ]);
    $contractType = ContractType::create([
        'company_id' => $company->id,
        'code' => 'PKWT',
        'name' => 'PKWT',
        'requires_end_date' => true,
    ]);
    $employee = Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'EMP-2026-0001',
        'full_name' => 'Budi Saneng',
        'name' => 'Budi Saneng',
        'nik' => '3374010101010001',
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);

    return [
        'contract' => [
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'contract_type_id' => $contractType->id,
            'position_id' => $position->id,
            'department_id' => $department->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ],
    ];
}
