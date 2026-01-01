<?php

namespace App\Domain\Exchange\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchingService
{
    public function attemptMatchSelection(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {

            $order = Order::query()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return;
            }

            if (! $order->isOpenOrder()) {
                return;
            }

            $counter = $this->findAndLockCounterOrder($order);

            if (! $counter) {
                return;
            }

            Log::info('Match candidate found', [
                'order_id' => $order->id,
                'order_side' => $order->side,
                'order_symbol' => $order->symbol,
                'order_price' => (string) $order->price,
                'order_amount' => (string) $order->amount,
                'counter_order_id' => $counter->id,
                'counter_side' => $counter->side,
                'counter_price' => (string) $counter->price,
                'counter_amount' => (string) $counter->amount,
            ]);

        }, 3);
    }

    private function findAndLockCounterOrder(Order $order): ?Order
    {
        $query = Order::query()
            ->whereSymbol($order->symbol)
            ->whereStatus(OrderStatus::OPEN->value)
            ->whereKeyNot($order->id);

        if ($order->side === OrderSide::BUY) {
            $query->whereSide(OrderSide::SELL->value)
                ->where('price', '<=', $order->price)
                ->orderBy('price')
                ->orderBy('created_at')
                ->orderBy('id');
        } else {
            $query->whereSide(OrderSide::BUY->value)
                ->where('price', '>=', $order->price)
                ->orderByDesc('price')
                ->orderBy('created_at')
                ->orderBy('id');
        }

        $counter = $query->lockForUpdate()->first();

        if (! $counter) {
            return null;
        }

        if (Money::cmp($counter->amount, $order->amount, Money::ASSET_SCALE) !== 0) {
            return null;

        }

        return $counter;
    }
}
