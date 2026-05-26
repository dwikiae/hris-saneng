<?php

use App\Application\Employee\CreateEmployeeService;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

require_once __DIR__.'/EmployeeCoreTestSupport.php';

it('rejects create employee without consent', function () {
    $fixture = employeeCoreFixture(['employee.create']);
    $payload = employeeCorePayload($fixture);
    unset($payload['consent_at']);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(CreateEmployeeService::class)->execute($payload))
        ->toThrow(ValidationException::class);
});

it('rejects create employee with invalid nik', function () {
    $fixture = employeeCoreFixture(['employee.create']);
    $payload = employeeCorePayload($fixture, ['nik' => '123']);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(CreateEmployeeService::class)->execute($payload))
        ->toThrow(ValidationException::class);
});

it('creates pending employee without employee number and writes chatter log', function () {
    $fixture = employeeCoreFixture(['employee.create']);

    $this->actingAs($fixture['actor']);

    $employee = app(CreateEmployeeService::class)->execute(employeeCorePayload($fixture));

    expect($employee->status)->toBe(Employee::PENDING)
        ->and($employee->employee_number)->toBeNull()
        ->and($employee->created_by)->toBe($fixture['actor']->id)
        ->and($employee->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();
});
