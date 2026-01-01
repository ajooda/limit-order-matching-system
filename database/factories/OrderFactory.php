<?php

namespace Database\Factories;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Support\FeeCalculator;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => 'BTC',
            'side' => OrderSide::BUY->value,
            'status' => OrderStatus::OPEN->value,
            'price' => '95000.00000000',
            'amount' => '0.010000000000000000',
            'locked_usd' => '0.00000000',
            'filled_at' => null,
            'cancelled_at' => null,
        ];
    }

   
    public function buyOpen(
        string $symbol = 'BTC',
        string $price = '95000.00000000',
        string $amount = '0.010000000000000000'
    ): static {
        return $this->state(fn () => [
            'symbol' => $symbol,
            'side' => OrderSide::BUY->value,
            'status' => OrderStatus::OPEN->value,
            'price' => $price,
            'amount' => $amount,
            'locked_usd' => FeeCalculator::calculateTotal($price, $amount, Money::USD_SCALE),
        ])->afterCreating(function (Order $order) {
            $buyer = User::query()->findOrFail($order->user_id);
            $buyer->balance_usd = Money::sub($buyer->balance_usd, $order->locked_usd, Money::USD_SCALE);
            $buyer->save();
        });
    }


    public function sellOpen(
        string $symbol = 'BTC',
        string $price = '95000.00000000',
        string $amount = '0.010000000000000000'
    ): static {
        return $this->state(fn () => [
            'symbol' => $symbol,
            'side' => OrderSide::SELL->value,
            'status' => OrderStatus::OPEN->value,
            'price' => $price,
            'amount' => $amount,
            'locked_usd' => '0.00000000',
        ])->afterCreating(function (Order $order) {
            $asset = Asset::query()
                ->firstOrCreate(
                    ['user_id' => $order->user_id, 'symbol' => $order->symbol],
                    ['amount' => '0.000000000000000000', 'locked_amount' => '0.000000000000000000']
                );
            $asset->amount = Money::sub($asset->amount, $order->amount, Money::ASSET_SCALE);
            $asset->locked_amount = Money::add($asset->locked_amount, $order->amount, Money::ASSET_SCALE);
            $asset->save();
        });
    }
}
