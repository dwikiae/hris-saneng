<?php

use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Models\User;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('searches and filters employees through the repository', function () {
    $fixture = employeeRepositoryFixture();

    /** @var EmployeeRepositoryInterface $employees */
    $employees = app(EmployeeRepositoryInterface::class);

    employeeRepositoryEmployee($fixture, 'EMP-2026-0001', 'Budi Saneng', Employee::PENDING);
    employeeRepositoryEmployee($fixture, 'EMP-2026-0002', 'Siti Saneng', Employee::ACTIVE);

    $result = $employees->findAll([
        'search' => 'Budi',
        'status' => Employee::PENDING,
        'department_id' => $fixture['department']->id,
        'employee_type_id' => $fixture['employmentType']->id,
    ], 20);

    expect($result->total())->toBe(1)
        ->and($result->first()->full_name)->toBe('Budi Saneng');
});

it('excludes archived employees by default and includes them on request', function () {
    $fixture = employeeRepositoryFixture();

    /** @var EmployeeRepositoryInterface $employees */
    $employees = app(EmployeeRepositoryInterface::class);

    employeeRepositoryEmployee($fixture, 'EMP-2026-0001', 'Active Employee', Employee::ACTIVE);
    employeeRepositoryEmployee($fixture, 'EMP-2026-0002', 'Archived Employee', Employee::PENDING, [
        'archived_at' => now(),
    ]);

    expect($employees->findAll([], 20)->total())->toBe(1)
        ->and($employees->findAll(['include_archived' => true], 20)->total())->toBe(2);
});

it('generates employee numbers atomically by company and year', function () {
    $fixture = employeeRepositoryFixture();

    /** @var EmployeeRepositoryInterface $employees */
    $employees = app(EmployeeRepositoryInterface::class);

    employeeRepositoryEmployee($fixture, 'EMP-'.date('Y').'-0007', 'Existing Employee', Employee::ACTIVE);

    expect($employees->generateEmployeeNumber($fixture['company']->id))->toBe('EMP-'.date('Y').'-0008');
});

it('queries contracts through the repository and enforces one active contract', function () {
    $fixture = employeeRepositoryFixture();

    /** @var ContractRepositoryInterface $contracts */
    $contracts = app(ContractRepositoryInterface::class);

    $employee = employeeRepositoryEmployee($fixture, 'EMP-2026-0001', 'Contract Employee', Employee::ACTIVE);

    $active = $contracts->create(employeeRepositoryContractPayload($fixture, $employee, [
        'contract_number' => 'CTR-2026-0001',
        'status' => Contract::ACTIVE,
    ]));

    $expired = $contracts->create(employeeRepositoryContractPayload($fixture, $employee, [
        'contract_number' => 'CTR-2026-0002',
        'status' => Contract::EXPIRED,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]));

    expect($contracts->findById($active->id)->is($active))->toBeTrue()
        ->and($contracts->findActiveByEmployee($employee->id)?->is($active))->toBeTrue()
        ->and($contracts->listByEmployee($employee->id))->toHaveCount(2)
        ->and($contracts->generateContractNumber($fixture['company']->id))->toBe('CTR-'.date('Y').'-0003');

    expect(fn () => $contracts->create(employeeRepositoryContractPayload($fixture, $employee, [
        'contract_number' => 'CTR-2026-0003',
        'status' => Contract::ACTIVE,
    ])))->toThrow(QueryException::class);

    expect($expired->refresh()->status)->toBe(Contract::EXPIRED);
});

/**
 * @return array<string, mixed>
 */
function employeeRepositoryFixture(): array
{
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);

    $user = User::create([
        'company_id' => $company->id,
        'name' => 'HR Admin Repository',
        'email' => 'hr.repository@saneng.co.id',
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

    $employmentType = EmploymentType::create([
        'company_id' => $company->id,
        'code' => 'PKWT',
        'name' => 'PKWT',
    ]);

    $contractType = ContractType::create([
        'company_id' => $company->id,
        'code' => 'PKWT',
        'name' => 'PKWT',
        'requires_end_date' => true,
    ]);

    return compact('company', 'user', 'department', 'position', 'employmentType', 'contractType');
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $overrides
 */
function employeeRepositoryEmployee(
    array $fixture,
    string $employeeNumber,
    string $fullName,
    string $status,
    array $overrides = []
): Employee {
    return Employee::create(array_merge([
        'company_id' => $fixture['company']->id,
        'employee_number' => $employeeNumber,
        'full_name' => $fullName,
        'name' => $fullName,
        'department_id' => $fixture['department']->id,
        'position_id' => $fixture['position']->id,
        'employment_type_id' => $fixture['employmentType']->id,
        'nik' => '3374010101010001',
        'consent_at' => now(),
        'consent_by' => $fixture['user']->id,
        'status' => $status,
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function employeeRepositoryContractPayload(array $fixture, Employee $employee, array $overrides = []): array
{
    return array_merge([
        'company_id' => $fixture['company']->id,
        'employee_id' => $employee->id,
        'contract_number' => 'CTR-2026-9999',
        'contract_type_id' => $fixture['contractType']->id,
        'position_id' => $fixture['position']->id,
        'department_id' => $fixture['department']->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => Contract::DRAFT,
    ], $overrides);
}
