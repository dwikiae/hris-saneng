<?php

namespace App\Domain\Employee\Rules;

use InvalidArgumentException;

final class NikFormatRule
{
    public function passes(string $nik): bool
    {
        return preg_match('/^\d{16}$/', $nik) === 1;
    }

    public function assert(string $nik): void
    {
        if (! $this->passes($nik)) {
            throw new InvalidArgumentException('employee.validation.nik_format');
        }
    }
}
