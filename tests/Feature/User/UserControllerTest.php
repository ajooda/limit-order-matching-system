<?php

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns user profile with assets', function () {
    $user = User::factory()->buyer('10000.00000000')->create();
    $btcAsset = Asset::factory()->for($user)->btc('1.000000000000000000')->create();
    $ethAsset = Asset::factory()->for($user)->eth('10.000000000000000000')->create();

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'balance_usd',
                'assets' => [
                    '*' => ['id', 'symbol', 'amount', 'locked_amount'],
                ],
            ],
        ]);

    $response->assertJsonPath('data.id', $user->id);
    $response->assertJsonPath('data.balance_usd', '10000.00000000');
    expect($response->json('data.assets'))->toHaveCount(2);
    expect($response->json('data.assets.0.symbol'))->toBeIn(['BTC', 'ETH']);
    expect($response->json('data.assets.1.symbol'))->toBeIn(['BTC', 'ETH']);
});

it('returns user profile via /api/profile/user endpoint', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile/user');

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('returns empty assets array when user has no assets', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertOk()
        ->assertJsonPath('data.assets', []);
});

it('requires authentication to get profile', function () {
    $response = $this->getJson('/api/profile');

    $response->assertUnauthorized();
});

it('returns paginated user orders', function () {
    $user = User::factory()->create();
    Order::factory()->count(25)->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'symbol', 'side', 'status', 'price', 'amount'],
            ],
            'links',
            'meta',
        ]);

    expect($response->json('data'))->toHaveCount(20); // Default per_page
});

it('filters orders by symbol', function () {
    $user = User::factory()->create();
    Order::factory()->count(5)->for($user)->create(['symbol' => 'BTC']);
    Order::factory()->count(3)->for($user)->create(['symbol' => 'ETH']);

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?symbol=BTC');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    foreach ($response->json('data') as $order) {
        expect($order['symbol'])->toBe('BTC');
    }
});

it('filters orders by side', function () {
    $user = User::factory()->create();
    Order::factory()->count(5)->for($user)->create(['side' => OrderSide::BUY->value]);
    Order::factory()->count(3)->for($user)->create(['side' => OrderSide::SELL->value]);

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?side=buy');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    foreach ($response->json('data') as $order) {
        expect($order['side'])->toBe(OrderSide::BUY->value);
    }
});

it('filters orders by status', function () {
    $user = User::factory()->create();
    Order::factory()->count(5)->for($user)->create(['status' => OrderStatus::OPEN->value]);
    Order::factory()->count(3)->for($user)->create(['status' => OrderStatus::FILLED->value]);
    Order::factory()->count(2)->for($user)->create(['status' => OrderStatus::CANCELLED->value]);

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?status=open');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    foreach ($response->json('data') as $order) {
        expect($order['status'])->toBe(OrderStatus::OPEN->value);
    }
});

it('respects per_page parameter', function () {
    $user = User::factory()->create();
    Order::factory()->count(30)->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?per_page=10');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(10);
});

it('combines multiple filters', function () {
    $user = User::factory()->create();
    Order::factory()->count(3)->for($user)->create([
        'symbol' => 'BTC',
        'side' => OrderSide::BUY->value,
        'status' => OrderStatus::OPEN->value,
    ]);
    Order::factory()->count(2)->for($user)->create([
        'symbol' => 'BTC',
        'side' => OrderSide::SELL->value,
        'status' => OrderStatus::OPEN->value,
    ]);

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?symbol=BTC&side=buy&status=open');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
    foreach ($response->json('data') as $order) {
        expect($order['symbol'])->toBe('BTC');
        expect($order['side'])->toBe(OrderSide::BUY->value);
        expect($order['status'])->toBe(OrderStatus::OPEN->value);
    }
});

it('returns orders ordered by created_at desc then id desc', function () {
    $user = User::factory()->create();
    $order1 = Order::factory()->for($user)->create(['created_at' => now()->subMinutes(10)]);
    $order2 = Order::factory()->for($user)->create(['created_at' => now()->subMinutes(5)]);
    $order3 = Order::factory()->for($user)->create(['created_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders');

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($order3->id);
    expect($response->json('data.1.id'))->toBe($order2->id);
    expect($response->json('data.2.id'))->toBe($order1->id);
});

it('validates symbol filter must be BTC or ETH', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?symbol=INVALID');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol']);
});

it('validates side filter must be buy or sell', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?side=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['side']);
});

it('validates status filter must be open, filled, or cancelled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?status=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('validates per_page must be between 1 and 100', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?per_page=0');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);

    $response = $this->actingAs($user)->getJson('/api/profile/my-orders?per_page=101');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});

it('only returns orders for authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Order::factory()->count(5)->for($user1)->create();
    Order::factory()->count(3)->for($user2)->create();

    $response = $this->actingAs($user1)->getJson('/api/profile/my-orders');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
    foreach ($response->json('data') as $order) {
        expect($order['id'])->not->toBeIn(Order::where('user_id', $user2->id)->pluck('id')->toArray());
    }
});

it('requires authentication to get my orders', function () {
    $response = $this->getJson('/api/profile/my-orders');

    $response->assertUnauthorized();
});
