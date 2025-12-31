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

    public static function cmp(string $a, string $b, int $scale = self::USD_SCALE): int
    {
        self::assertNumeric($a);
        self::assertNumeric($b);

        return bccomp($a, $b, $scale);
    }

    public static function gte(string $a, string $b, int $scale = self::USD_SCALE): bool
    {
        return self::cmp($a, $b, $scale) >= 0;
    }

    public static function percentage(string $amount, string $percent, int $scale = self::USD_SCALE): string
    {
        self::assertNumeric($amount);
        self::assertNumeric($percent);

        $fraction = bcdiv($percent, '100', $scale);

        return self::mul($amount, $fraction, $scale);
    }

    private static function assertNumeric(string $value): void
    {
        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException("Invalid numeric string: [{$value}]");
        }
    }
}
