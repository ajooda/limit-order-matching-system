<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates test users and assets as specified in TEST_SCENARIO.md
     */
    public function run(): void
    {
        User::factory()
            ->state([
                'name' => 'Buyer 1',
                'email' => 'buyer1@test.com',
                'balance_usd' => '10000.00000000',
            ])
            ->create();

        $userB = User::factory()
            ->state([
                'name' => 'Buyer 2',
                'email' => 'buyer2@test.com',
                'balance_usd' => '10000.00000000',
            ])
            ->create();

        $sellerA = User::factory()
            ->state([
                'name' => 'Seller 1',
                'email' => 'seller1@test.com',
                'balance_usd' => '10000.00000000',
            ])
            ->create();

        $sellerB = User::factory()
            ->state([
                'name' => 'Seller 2',
                'email' => 'seller2@test.com',
                'balance_usd' => 0,
            ])
            ->create();

        Asset::factory()
            ->for($sellerA)
            ->btc('1.000000000000000000')
            ->eth('2.000000000000000000')
            ->create();

        Asset::factory()
            ->for($sellerB)
            ->eth('1.000000000000000000')
            ->create();

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Test users created (all passwords: "password"):');
        $this->command->info('  - buyer1@test.com (Buyer 1) - $10,000 USD');
        $this->command->info('  - buyer2@test.com (Buyer 2) - $10,000 USD');
        $this->command->info('  - seller1@test.com (Seller 1) - $10,000 USD, 1.0 BTC, 2.0 ETH');
        $this->command->info('  - seller2@test.com (Seller 2) - $0 USD, 1.0 ETH');
    }
}
