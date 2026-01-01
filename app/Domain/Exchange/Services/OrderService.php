<?php

namespace App\Domain\Exchange\Services;

use App\Domain\Exchange\DTO\CreateOrderData;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Support\FeeCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(User $user, CreateOrderData $dto): Order
    {
        return DB::transaction(function () use ($user, $dto) {
            return match ($dto->side) {
                OrderSide::BUY => $this->createBuyOrder($user, $dto),
                OrderSide::SELL => $this->createSellOrder($user, $dto),
            };
        }, 3);
    }

    /**
     * @throws ValidationException
     */
    private function createBuyOrder(User $user, CreateOrderData $dto): Order
    {
        $lockedUser = $this->getLockedUser($user->id);

        $lockTotal = FeeCalculator::calculateTotal($dto->price, $dto->amount);

        if (! Money::gte($lockedUser->balance_usd, $lockTotal, Money::USD_SCALE)) {
            throw ValidationException::withMessages([
                'balance_usd' => 'Insufficient USD balance.',
            ]);
        }

        $lockedUser->balance_usd = Money::sub($lockedUser->balance_usd, $lockTotal, Money::USD_SCALE);

        $lockedUser->save();

        $order = $lockedUser->orders()->create([
            'symbol' => $dto->symbol,
            'side' => OrderSide::BUY->value,
            'status' => OrderStatus::OPEN->value,
            'price' => $dto->price,
            'amount' => $dto->amount,
            'locked_usd' => $lockTotal,
        ]);

        // TODO: Dispatch matching job

        return $order;

    }

    /**
     * @throws ValidationException
     */
    private function createSellOrder(User $user, CreateOrderData $dto): Order
    {
        $asset = $user->assets()
            ->whereSymbol($dto->symbol)
            ->lockForUpdate()
            ->first();

        if (! $asset) {
            $user->assets()->create([
                'symbol' => $dto->symbol,
                'amount' => '0',
                'locked_amount' => '0',
            ]);

            $asset = Asset::query()
                ->whereKey($asset->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        if (! Money::gte($asset->amount, $dto->amount, Money::ASSET_SCALE)) {
            throw ValidationException::withMessages([
                'amount' => "Insufficient {$dto->symbol} balance.",
            ]);
        }

        $asset->amount = Money::sub($asset->amount, $dto->amount, Money::ASSET_SCALE);
        $asset->locked_amount = Money::add($asset->locked_amount, $dto->amount, Money::ASSET_SCALE);
        $asset->save();

        $order = $user->orders()->create([
            'symbol' => $dto->symbol,
            'side' => OrderSide::SELL->value,
            'status' => OrderStatus::OPEN->value,
            'price' => $dto->price,
            'amount' => $dto->amount,
            'locked_usd' => '0',
        ]);

        // TODO: Dispatch matching job

        return $order;
    }

    public function cancelOrder(User $user, Order $order): Order
    {
        return DB::transaction(function () use ($user, $order) {

            /** @var Order $lockedOrder */
            $lockedOrder = $user->orders()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->isOpenOrder()) {
                throw ValidationException::withMessages([
                    'order' => 'Only open orders can be cancelled.',
                ]);
            }

            match ($lockedOrder->side) {
                OrderSide::BUY => $this->cancelBuyOrder($lockedOrder),
                OrderSide::SELL => $this->cancelSellOrder($user, $lockedOrder),
            };

            $lockedOrder->update([
                'status' => OrderStatus::CANCELLED->value,
                'cancelled_at' => now(),
            ]);

            return $lockedOrder->refresh();

        }, 3);

    }

    private function cancelBuyOrder(Order $order): void
    {
        $lockedUser = $this->getLockedUser($order->user_id);

        $refund = (string) $order->locked_usd;

        if (Money::cmp($refund, '0', Money::USD_SCALE) > 0) {
            $lockedUser->balance_usd = Money::add($lockedUser->balance_usd, $refund, Money::USD_SCALE);
            $lockedUser->save();
        }

        $order->locked_usd = '0';
        $order->save();
    }

    /**
     * @throws ValidationException
     */
    private function cancelSellOrder(User $user, Order $order): void
    {
        $lockedAsset = $user->assets()
            ->whereHas($order->symbol)
            ->lockForUpdate()
            ->firstOr(function () {
                throw ValidationException::withMessages([
                    'asset' => 'Asset wallet not found for this order.',
                ]);
            });

        $qty = $order->amount;

        if (Money::cmp($lockedAsset->locked_amount, $qty, Money::ASSET_SCALE) < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Locked asset amount is insufficient to cancel this order',
            ]);
        }

        $lockedAsset->update([
            'locked_amount' => Money::sub($lockedAsset->locked_amount, $qty, Money::ASSET_SCALE),
            'amount' => Money::add($lockedAsset->amount, $qty, Money::ASSET_SCALE),
        ]);

    }

    private function getLockedUser(string|int $userKey)
    {
        return User::query()
            ->whereKey($userKey)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
