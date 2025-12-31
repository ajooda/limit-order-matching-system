<?php

namespace App\Enums;

enum OrderSide: int
{
    case BUY = 1;
    case SELL = 2;

    public function label(): string
    {
        return match ($this) {
            self::BUY => 'buy',
            self::SELL => 'sell',
        };
    }
}
