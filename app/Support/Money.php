<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public const USD_SCALE = 8;

    public const ASSET_SCALE = 18;

    public static function add(string $a, string $b, int $scale = self::USD_SCALE): string
    {
        self::assertNumeric($a);
        self::assertNumeric($b);

        return bcadd($a, $b, $scale);
    }

    public static function sub(string $a, string $b, int $scale = self::USD_SCALE): string
    {
        self::assertNumeric($a);
        self::assertNumeric($b);

        return bcsub($a, $b, $scale);
    }

    public static function mul(string $a, string $b, int $scale = self::USD_SCALE): string
    {
        self::assertNumeric($a);
        self::assertNumeric($b);

        return bcmul($a, $b, $scale);
    }

    public static function div(string $a, string $b, int $scale = self::USD_SCALE): string
    {
        self::assertNumeric($a);
        self::assertNumeric($b);

        if (self::isZero($b, $scale)) {
            throw new InvalidArgumentException('Division by zero.');
        }

        return bcdiv($a, $b, $scale);
    }

    public static function cmp(string $a, string $b, int $scale = self::USD_SCALE): int
    {
        self::assertNumeric($a);
        self::assertNumeric($b);

        return bccomp($a, $b, $scale);
    }

    public static function isZero(string $a, int $scale = self::USD_SCALE): bool
    {
        self::assertNumeric($a);

        return bccomp($a, '0', $scale) === 0;
    }

    public static function gt(string $a, string $b, int $scale = self::USD_SCALE): bool
    {
        return self::cmp($a, $b, $scale) === 1;
    }

    public static function gte(string $a, string $b, int $scale = self::USD_SCALE): bool
    {
        return self::cmp($a, $b, $scale) >= 0;
    }

    public static function lt(string $a, string $b, int $scale = self::USD_SCALE): bool
    {
        return self::cmp($a, $b, $scale) === -1;
    }

    public static function lte(string $a, string $b, int $scale = self::USD_SCALE): bool
    {
        return self::cmp($a, $b, $scale) <= 0;
    }

    public static function min(string $a, string $b, int $scale = self::USD_SCALE): string
    {
        return self::lte($a, $b, $scale) ? $a : $b;
    }

    public static function max(string $a, string $b, int $scale = self::USD_SCALE): string
    {
        return self::gte($a, $b, $scale) ? $a : $b;
    }

    public static function percentage(string $amount, string $percent, int $scale = self::USD_SCALE): string
    {
        self::assertNumeric($amount);
        self::assertNumeric($percent);

        $fraction = self::div($percent, '100', $scale);

        return self::mul($amount, $fraction, $scale);
    }

    private static function assertNumeric(string $value): void
    {
        // Accept "10", "10.5", "-0.01" (no commas, no spaces)
        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException("Invalid numeric string: [{$value}]");
        }
    }
}
