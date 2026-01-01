<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'symbol' => 'BTC',
            'amount' => '0.000000000000000000',
            'locked_amount' => '0.000000000000000000',
        ];
    }

    public function btc(string $amount = '1.000000000000000000'): static
    {
        return $this->state(fn () => [
            'symbol' => 'BTC',
            'amount' => $amount,
        ]);
    }

    public function eth(string $amount = '10.000000000000000000'): static
    {
        return $this->state(fn () => [
            'symbol' => 'ETH',
            'amount' => $amount,
        ]);
    }
}
