<?php

namespace App\Http\Controllers\Api;

use App\Domain\Exchange\DTO\CreateOrderData;
use App\Domain\Exchange\Services\OrderService;
use App\Enums\OrderSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function storeOrder(StoreOrderRequest $request): OrderResource
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

    public function cancelOrder(Request $request, string $orderId): OrderResource
    {
        /** @var User $user */
        $user = $request->user();

        $order = $user->orders()
            ->whereKey($orderId)
            ->firstOrFail();

        $updated = $this->orderService->cancelOrder($user, $order);

        return new OrderResource($updated);

    }
}
