<?php

namespace App\Application\Recruitment;

class DecimalMath
{
    public function add(string $left, string $right): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, 2);
        }

        return $this->scaledToDecimal($this->decimalToScaled($left) + $this->decimalToScaled($right));
    }

    public function div(string $left, string $right, int $scale): string
    {
        if (function_exists('bcdiv')) {
            return bcdiv($left, $right, $scale);
        }

        $divisor = $this->decimalToScaled($right);

        if ($divisor === 0) {
            return '0.00';
        }

        $factor = 10 ** $scale;

        return $this->scaledToDecimal(intdiv($this->decimalToScaled($left) * $factor, $divisor), $scale);
    }

    public function mul(string $left, string $right, int $scale): string
    {
        if (function_exists('bcmul')) {
            return bcmul($left, $right, $scale);
        }

        return $this->scaledToDecimal(intdiv($this->decimalToScaled($left) * $this->decimalToScaled($right), 100), $scale);
    }

    public function greaterThan(string $left, string $right): bool
    {
        return $this->compare($left, $right) === 1;
    }

    public function compare(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 2);
        }

        return $this->decimalToScaled($left) <=> $this->decimalToScaled($right);
    }

    private function decimalToScaled(string $value): int
    {
        $normalized = str_contains($value, '.') ? $value : $value.'.00';
        [$whole, $decimal] = explode('.', $normalized, 2);

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function scaledToDecimal(int $value, int $scale = 2): string
    {
        $factor = 10 ** $scale;
        $whole = intdiv($value, $factor);
        $decimal = abs($value % $factor);

        return $whole.'.'.str_pad((string) $decimal, $scale, '0', STR_PAD_LEFT);
    }
}
