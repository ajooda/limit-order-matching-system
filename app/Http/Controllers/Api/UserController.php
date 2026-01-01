<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MyOrdersRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function getProfile(Request $request): UserResource
    {
        $user = $request->user()->load([
            'assets:id,user_id,symbol,amount,locked_amount',
        ]);

        return new UserResource($user);
    }

    public function getMyOrders(MyOrdersRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        $user = $request->user();
        $perPage = (int) ($data['per_page'] ?? 20);

        $ordersQuery = $user->orders()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($symbol = $request->validated('symbol')) {
            $ordersQuery->where('symbol', $symbol);
        }

        if ($side = $request->validated('side')) {
            $ordersQuery->where('side', $this->normalizeSide($side));
        }

        if ($status = $request->validated('status')) {
            $ordersQuery->where('status', $this->normalizeStatus($status));
        }

        return OrderResource::collection($ordersQuery->paginate($perPage));

    }

    private function normalizeSide(string $side): int
    {
        return match (strtolower($side)) {
            'buy' => OrderSide::BUY->value,
            'sell' => OrderSide::SELL->value,
        };
    }

    private function normalizeStatus(string|int $status): int
    {
        return match (strtolower((string) $status)) {
            'open' => OrderStatus::OPEN->value,
            'filled' => OrderStatus::FILLED->value,
            'cancelled' => OrderStatus::CANCELLED->value,
        };
    }
}
