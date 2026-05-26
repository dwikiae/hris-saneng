<?php

namespace App\Domain\Employee\Rules;

use DateTimeInterface;
use InvalidArgumentException;

final class ConsentRequiredRule
{
    public function passes(DateTimeInterface|string|null $consentAt, ?string $consentText): bool
    {
        return $consentAt !== null && trim((string) $consentText) !== '';
    }

    public function assert(DateTimeInterface|string|null $consentAt, ?string $consentText): void
    {
        if (! $this->passes($consentAt, $consentText)) {
            throw new InvalidArgumentException('employee.validation.consent_required');
        }
    }
}
