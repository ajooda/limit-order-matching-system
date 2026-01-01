<?php

namespace App\Support;

class FeeCalculator
{
    private const FEE_RATE = 0.015;

    public static function calculateFee(string $usdVolume, int $scale = Money::USD_SCALE): string
    {
        return Money::mul($usdVolume, self::FEE_RATE, $scale);
    }

    public static function calculateTotal(string $price, string $amount, int $scale = Money::USD_SCALE): string
    {
        $volume = Money::mul($price, $amount, $scale);
        $fee = self::calculateFee($volume, $scale);
        return Money::add($volume, $fee, $scale);
    }

}
