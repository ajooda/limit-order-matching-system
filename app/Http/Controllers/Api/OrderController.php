<?php

namespace App\Http\Controllers\Api;

use App\Domain\Exchange\DTO\CreateOrderData;
use App\Domain\Exchange\Services\OrderService;
use App\Enums\OrderSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    )
    {
    }

    public function store(StoreOrderRequest $request): OrderResource
    {
        $data = $request->validated();

        $side = match (strtolower($data['side'])) {
            'buy' => OrderSide::BUY,
            'sell' => OrderSide::SELL,
        };

        $dto = new CreateOrderData(
            symbol: $data['symbol'],
            side: $side,
            price: $data['price'],
            amount: $data['amount'],
        );

        $order = $this->orderService->createOrder($request->user(), $dto);

        return new OrderResource($order);
    }

}
