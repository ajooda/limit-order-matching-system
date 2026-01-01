<?php

namespace App\Domain\Exchange\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatchedEvent;
use App\Models\Order;
use App\Models\Trade;
use App\Support\FeeCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchingService
{
    public function attemptMatchSelection(int $orderId): void
    {
        $trade = DB::transaction(function () use ($orderId) {
            return $this->attemptMatch($orderId);
        }, 3);

        DB::afterCommit(function () use ($trade) {

            if ($trade) {
                $buyer = $trade->buyer()
                    ->with(['assets:id,user_id,symbol,amount,locked_amount'])
                    ->first();

                $seller = $trade->seller()
                    ->with(['assets:id,user_id,symbol,amount,locked_amount'])
                    ->first();

                event(new OrderMatchedEvent($trade, $buyer));
                event(new OrderMatchedEvent($trade, $seller));
            }
        });
    }

    /**
     * @throws ValidationException
     */
    public function attemptMatch(int $orderId): ?Trade
    {

        $order = Order::query()
            ->whereKey($orderId)
            ->lockForUpdate()
            ->first();

        if (! $order || ! $order->isOpenOrder()) {
            return null;
        }

        $counter = $this->findAndLockCounterOrder($order);

        if (! $counter) {
            return null;
        }

        [$buyOrder, $sellOrder] = $this->normalizeBuySell($order, $counter);

        $tradePrice = (string) $counter->price;

        $tradeAmount = (string) $buyOrder->amount;

        $volume = Money::mul($tradePrice, $tradeAmount, Money::USD_SCALE);
        $fee = FeeCalculator::calculateFee($volume, Money::USD_SCALE);
        $actualTotal = FeeCalculator::calculateTotal($tradePrice, $tradeAmount, Money::USD_SCALE);

        $buyer = $buyOrder->user()->lockForUpdate()->first();
        $seller = $sellOrder->user()->lockForUpdate()->first();

        $sellerAsset = $seller->assets()
            ->whereSymbol($sellOrder->symbol)
            ->lockForUpdate()
            ->firstOr(function () {
                throw ValidationException::withMessages([
                    'asset' => 'Seller asset wallet not found.',
                ]);
            });

        if (! Money::gte($sellerAsset->locked_amount, $tradeAmount, Money::ASSET_SCALE)) {
            throw ValidationException::withMessages([
                'asset' => 'Seller locked amount insufficient',
            ]);
        }

        if (! Money::gte($buyOrder->locked_usd, $actualTotal, Money::USD_SCALE)) {
            throw ValidationException::withMessages([
                'balance' => 'Buyer locked USD is insufficient for settlement (data integrity issue).',
            ]);
        }

        $refund = Money::sub($buyOrder->locked_usd, $actualTotal, Money::USD_SCALE);
        if (Money::cmp($refund, '0', Money::USD_SCALE) > 0) {
            $buyer->balance_usd = Money::add($buyer->balance_usd, $refund, Money::USD_SCALE);
        }

        $sellerAsset->update([
            'locked_amount' => Money::sub($sellerAsset->locked_amount, $tradeAmount, Money::ASSET_SCALE),
        ]);

        $buyerAsset = $buyer->assets()
            ->whereSymbol($buyOrder->symbol)
            ->lockForUpdate()
            ->first();

        if (! $buyerAsset) {
            $buyerAsset = $buyer->assets()->create([
                'symbol' => $buyOrder->symbol,
                'amount' => '0',
                'locked_amount' => '0',
            ]);

            $buyerAsset = $buyer->assets()
                ->whereKey($buyerAsset->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $buyerAsset->update([
            'amount' => Money::add($buyerAsset->amount, $tradeAmount, Money::ASSET_SCALE),
        ]);

        $seller->balance_usd = Money::add($seller->balance_usd, $volume, Money::USD_SCALE);

        $buyer->save();
        $seller->save();

        $now = now();

        $buyOrder->update([
            'status' => OrderStatus::FILLED->value,
            'filled_at' => $now,
            'locked_usd' => 0,
        ]);

        $sellOrder->update([
            'status' => OrderStatus::FILLED->value,
            'filled_at' => $now,
        ]);

        return Trade::query()->create([
            'symbol' => $buyOrder->symbol,
            'price' => $tradePrice,
            'amount' => $tradeAmount,
            'usd_volume' => $volume,
            'fee_usd' => $fee,
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

    }

    private function normalizeBuySell(Order $a, Order $b): array
    {
        if ((int) $a->side == OrderSide::BUY) {
            return [$a, $b];
        }

        return [$b, $a];
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

        if (! $counter || ! $counter->isOpenOrder()) {
            return null;
        }

        if (Money::cmp($counter->amount, $order->amount, Money::ASSET_SCALE) !== 0) {
            return null;
        }

        return $counter;
    }
}
