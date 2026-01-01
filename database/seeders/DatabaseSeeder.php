<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        //        User::factory()->create([
        //            'name' => 'Test User',
        //            'email' => 'test@example.com',
        //        ]);
        $buyer = User::factory()->buyer('2000.00000000')->create();
        $seller = User::factory()->seller()->create();
        Asset::factory()->for($seller)->btc('0.050000000000000000')->create();
        $sell = Order::factory()->for($seller)->sellOpen('BTC', '94900.00000000', '0.010000000000000000')->create();
        $buy = Order::factory()->for($buyer)->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->create();

    }
}
