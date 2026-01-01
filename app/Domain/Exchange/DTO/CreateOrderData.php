<?php

namespace App\Domain\Exchange\DTO;

use App\Enums\OrderSide;

final readonly class CreateOrderData
{
    public function __construct(
        public string $symbol,
        public OrderSide $side,
        public string $price,
        public string $amount
    ) {}

}
