<?php

namespace App\Http\Controllers\Api;

use App\Domain\Exchange\DTO\CreateOrderData;
use App\Domain\Exchange\Services\OrderService;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Support\FeeCalculator;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function getOrders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH'])],
        ]);

        $buyOrders = $this->getOrdersRecoreds($validated['symbol'], OrderSide::BUY, 'desc');

        $sellOrders = $this->getOrdersRecoreds($validated['symbol'], OrderSide::SELL, 'asc');

        return response()->json([
            'symbol' => $validated['symbol'],
            'buy' => OrderResource::collection($buyOrders),
            'sell' => OrderResource::collection($sellOrders),
        ]);

    }

    public function previewOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH'])],
            'side' => ['required', 'string', Rule::in(['buy', 'sell'])],
            'price' => ['required', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $price = (string) $validated['price'];
        $amount = (string) $validated['amount'];
        $side = strtolower($validated['side']);

        $volume = Money::mul($price, $amount, Money::USD_SCALE);
        $fee = '0.00000000';
        $total = $volume;

        if ($side === 'buy') {
            $fee = FeeCalculator::calculateFee($volume, Money::USD_SCALE);
            $total = FeeCalculator::calculateTotal($price, $amount, Money::USD_SCALE);
        }

        return response()->json([
            'volume' => $volume,
            'fee' => $fee,
            'total' => $total,
            'fee_rate' => FeeCalculator::FEE_RATE,
        ]);
    }

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

    private function getOrdersRecoreds($symbol, OrderSide $side, $priceDirection = 'asc'): Collection
    {
        return Order::query()
            ->select(['id', 'symbol', 'side', 'status', 'price', 'amount', 'created_at'])
            ->wheresymbol($symbol)
            ->whereStatus(OrderStatus::OPEN->value)
            ->whereSide($side->value)
            ->orderBy('price', $priceDirection)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
