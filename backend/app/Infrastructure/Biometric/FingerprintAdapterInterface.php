<?php

namespace App\Infrastructure\Biometric;

interface FingerprintAdapterInterface
{
    public function isAvailable(): bool;
}
