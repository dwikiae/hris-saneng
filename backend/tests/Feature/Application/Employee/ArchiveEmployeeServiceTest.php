<?php

use App\Application\Employee\ArchiveEmployeeService;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

require_once __DIR__.'/EmployeeCoreTestSupport.php';

it('archives pending employee and writes system log', function () {
    $fixture = employeeCoreFixture(['employee.archive']);
    $employee = employeeCoreEmployee($fixture);

    $this->actingAs($fixture['actor']);

    app(ArchiveEmployeeService::class)->execute($employee);

    $archived = Employee::withArchived()->findOrFail($employee->id);

    expect($archived->archived_at)->not->toBeNull()
        ->and($archived->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();
});

it('rejects archive when employee is already active', function () {
    $fixture = employeeCoreFixture(['employee.archive']);
    $employee = employeeCoreEmployee($fixture, ['status' => Employee::ACTIVE]);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(ArchiveEmployeeService::class)->execute($employee))
        ->toThrow(ValidationException::class);
});
