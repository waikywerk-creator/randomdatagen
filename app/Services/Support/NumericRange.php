<?php

namespace App\Services\Support;

final class NumericRange
{
    /** @return array{int, int, int, int} */
    public static function forColumn(array $column): array
    {
        $decimals = $column['type'] === 'integer' ? 0 : (int) ($column['decimals'] ?? 2);

        return [
            self::scaled($column['min'] ?? 0, $decimals, true),
            self::scaled($column['max'] ?? ($column['type'] === 'percentage' ? 100 : 1000), $decimals, false),
            $decimals,
            10 ** $decimals,
        ];
    }

    private static function scaled(int|float|string $number, int $decimals, bool $roundUp): int
    {
        // Work from decimal digits to avoid floating-point errors at exact bounds
        // such as 0.07, while retaining tiny fractions and scientific notation.
        preg_match('/^([+-]?)(\d*)(?:\.(\d*))?(?:[eE]([+-]?\d+))?$/D', (string) $number, $parts);
        $digits = $parts[2].($parts[3] ?? '');
        if (trim($digits, '0') === '') {
            return 0;
        }

        $position = strlen($parts[2]) + (int) ($parts[4] ?? 0) + $decimals;
        if ($position <= 0) {
            $whole = 0;
            $hasFraction = true;
        } elseif ($position >= strlen($digits)) {
            $whole = (int) ($digits.str_repeat('0', $position - strlen($digits)));
            $hasFraction = false;
        } else {
            $whole = (int) substr($digits, 0, $position);
            $hasFraction = trim(substr($digits, $position), '0') !== '';
        }

        if ($parts[1] === '-') {
            return -($whole + ($hasFraction && ! $roundUp ? 1 : 0));
        }

        return $whole + ($hasFraction && $roundUp ? 1 : 0);
    }
}
