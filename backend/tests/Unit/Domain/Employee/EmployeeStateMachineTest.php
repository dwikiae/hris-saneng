<?php

use App\Domain\Employee\EmployeeStateMachine;
use App\Domain\Employee\EmployeeStatus;

it('allows pending to active transition', function () {
    $stateMachine = new EmployeeStateMachine();

    expect($stateMachine->canTransition(EmployeeStatus::Pending, EmployeeStatus::Active))->toBeTrue();

    $stateMachine->assertCanTransition(EmployeeStatus::Pending, EmployeeStatus::Active);
})->group('employee-domain');

it('rejects active to pending transition', function () {
    $stateMachine = new EmployeeStateMachine();

    expect($stateMachine->canTransition(EmployeeStatus::Active, EmployeeStatus::Pending))->toBeFalse();

    $stateMachine->assertCanTransition(EmployeeStatus::Active, EmployeeStatus::Pending);
})->throws(DomainException::class)->group('employee-domain');

it('keeps archived employee records terminal in the state machine', function () {
    $stateMachine = new EmployeeStateMachine();

    expect($stateMachine->canTransition(EmployeeStatus::Archived, EmployeeStatus::Active))->toBeFalse()
        ->and($stateMachine->canTransition(EmployeeStatus::Archived, EmployeeStatus::Pending))->toBeFalse();
})->group('employee-domain');
