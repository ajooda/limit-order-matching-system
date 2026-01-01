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
        $lockedUser = User::query()
            ->whereKey($user->id)
            ->lockForUpdate()
            ->firstOrFail();

        $lockTotal = FeeCalculator::calculateTotal($dto->price, $dto->amount);

        if (!Money::gte($lockedUser->balance_usd, $lockTotal, Money::USD_SCALE)) {
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

        if (!$asset) {
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

        if (!Money::gte($asset->amount, $dto->amount, Money::ASSET_SCALE)) {
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

}
