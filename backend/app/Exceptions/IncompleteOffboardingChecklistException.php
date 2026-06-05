<?php

namespace App\Exceptions;

use RuntimeException;

class IncompleteOffboardingChecklistException extends RuntimeException
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(private readonly array $items)
    {
        parent::__construct('employee.offboarding.incomplete_checklist');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }
}
