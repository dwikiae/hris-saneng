<?php

use App\Application\Employee\ApproveEmployeeService;
use App\Jobs\Employee\SendApprovalResultNotificationJob;
use App\Models\Employee;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

require_once __DIR__.'/EmployeeCoreTestSupport.php';

it('forbids approval by user who is not assigned approver', function () {
    $fixture = employeeCoreFixture(['employee.approve']);
    $otherApprover = employeeCoreUser($fixture['company'], 'other_approver', ['employee.approve']);
    $employee = employeeCoreEmployee($fixture, ['approver_id' => $otherApprover->id]);

    $this->actingAs($fixture['actor']);

    expect(fn () => app(ApproveEmployeeService::class)->execute($employee))
        ->toThrow(AuthorizationException::class);
});

it('approves pending employee and generates employee number', function () {
    Queue::fake();
    $fixture = employeeCoreFixture(['employee.approve']);
    $employee = employeeCoreEmployee($fixture, ['approver_id' => $fixture['actor']->id]);

    $this->actingAs($fixture['actor']);

    $updated = app(ApproveEmployeeService::class)->execute($employee);

    expect($updated->status)->toBe(Employee::ACTIVE)
        ->and($updated->employee_number)->toBe('EMP-'.date('Y').'-0001')
        ->and($updated->approved_by)->toBe($fixture['actor']->id)
        ->and($updated->chatterMessages()->where('type', 'system_log')->exists())->toBeTrue();

    Queue::assertPushed(SendApprovalResultNotificationJob::class);
});
