<?php

namespace App\Domain\Employee;

use DomainException;

final class EmployeeStateMachine
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        EmployeeStatus::Pending->value => [
            EmployeeStatus::Active->value,
        ],
        EmployeeStatus::Active->value => [],
        EmployeeStatus::Archived->value => [],
    ];

    public function canTransition(EmployeeStatus $from, EmployeeStatus $to): bool
    {
        return in_array($to->value, self::ALLOWED_TRANSITIONS[$from->value], true);
    }

    public function assertCanTransition(EmployeeStatus $from, EmployeeStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new DomainException("employee.transition.invalid:{$from->value}:{$to->value}");
        }
    }
}
