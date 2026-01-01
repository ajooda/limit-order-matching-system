<?php

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Jobs\MatchOrderJob;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Support\FeeCalculator;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('returns buy and sell orders for a symbol', function () {
    $user = User::factory()->create();

    $buyOrder1 = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->create();
    $buyOrder2 = Order::factory()->buyOpen('BTC', '94000.00000000', '0.010000000000000000')->create();
    $sellOrder1 = Order::factory()->sellOpen('BTC', '96000.00000000', '0.010000000000000000')->create();
    $sellOrder2 = Order::factory()->sellOpen('BTC', '97000.00000000', '0.010000000000000000')->create();

    // Create filled order (should not appear)
    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => OrderSide::BUY->value,
        'status' => OrderStatus::FILLED->value,
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $response->assertOk()
        ->assertJsonStructure([
            'symbol',
            'buy' => [
                '*' => ['id', 'symbol', 'side', 'status', 'price', 'amount', 'created_at'],
            ],
            'sell' => [
                '*' => ['id', 'symbol', 'side', 'status', 'price', 'amount', 'created_at'],
            ],
        ]);

    $response->assertJsonPath('symbol', 'BTC');
    expect($response->json('buy'))->toHaveCount(2);
    expect($response->json('sell'))->toHaveCount(2);

    // Buy orders should be descending by price
    expect($response->json('buy.0.price'))->toBe('95000.00000000');
    expect($response->json('buy.1.price'))->toBe('94000.00000000');

    // Sell orders should be ascending by price
    expect($response->json('sell.0.price'))->toBe('96000.00000000');
    expect($response->json('sell.1.price'))->toBe('97000.00000000');
});

it('requires authentication to get orders', function () {
    $response = $this->getJson('/api/orders?symbol=BTC');

    $response->assertUnauthorized();
});

it('validates symbol is required for get orders', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/orders');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol']);
});

it('validates symbol must be BTC or ETH for get orders', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=INVALID');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol']);
});

it('returns empty arrays when no orders exist for symbol', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $response->assertOk()
        ->assertJsonPath('buy', [])
        ->assertJsonPath('sell', []);
});

it('returns orders for ETH symbol', function () {
    $user = User::factory()->create();

    $buyOrder = Order::factory()->buyOpen('ETH', '3000.00000000', '0.100000000000000000')->create();
    $sellOrder = Order::factory()->sellOpen('ETH', '3100.00000000', '0.100000000000000000')->create();

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=ETH');

    $response->assertOk()
        ->assertJsonPath('symbol', 'ETH');
    expect($response->json('buy'))->toHaveCount(1);
    expect($response->json('sell'))->toHaveCount(1);
    expect($response->json('buy.0.symbol'))->toBe('ETH');
    expect($response->json('sell.0.symbol'))->toBe('ETH');
});

it('creates a buy order with sufficient balance', function () {
    $user = User::factory()->buyer('100000.00000000')->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'symbol',
                'side',
                'status',
                'price',
                'amount',
                'locked_usd',
                'created_at',
            ],
        ]);

    $response->assertJsonPath('data.symbol', 'BTC');
    $response->assertJsonPath('data.side', OrderSide::BUY->value);
    $response->assertJsonPath('data.status', OrderStatus::OPEN->value);
    $response->assertJsonPath('data.price', '95000.00000000');
    $response->assertJsonPath('data.amount', '0.010000000000000000');

    $order = Order::find($response->json('data.id'));
    expect($order)->not->toBeNull();
    expect($order->locked_usd)->not->toBe('0.00000000');

    $user->refresh();
    $expectedLock = FeeCalculator::calculateTotal('95000.00000000', '0.010000000000000000', Money::USD_SCALE);
    expect($user->balance_usd)->toBe(Money::sub('100000.00000000', $expectedLock, Money::USD_SCALE));

    Queue::assertPushed(MatchOrderJob::class, function ($job) use ($order) {
        return $job->orderId === $order->id;
    });
});

it('creates a sell order with sufficient asset balance', function () {
    $user = User::factory()->create();
    Asset::factory()->for($user)->btc('1.000000000000000000')->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.symbol', 'BTC');
    $response->assertJsonPath('data.side', OrderSide::SELL->value);
    $response->assertJsonPath('data.status', OrderStatus::OPEN->value);
    $response->assertJsonPath('data.locked_usd', '0.00000000'); // Sell orders don't lock USD (value is 0)

    $order = Order::find($response->json('data.id'));
    expect($order)->not->toBeNull();

    $asset = $user->assets()->where('symbol', 'BTC')->first();
    expect($asset->amount)->toBe('0.990000000000000000');
    expect($asset->locked_amount)->toBe('0.010000000000000000');

    Queue::assertPushed(MatchOrderJob::class);
});

it('fails to create sell order when asset wallet does not exist and has zero balance', function () {
    $user = User::factory()->create();
    // No asset created - will be auto-created with 0 amount in transaction, but rolled back on validation failure

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'ETH',
        'side' => 'sell',
        'price' => '3000.00000000',
        'amount' => '0.100000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);

    // Asset wallet should not exist because transaction was rolled back
    $asset = $user->assets()->where('symbol', 'ETH')->first();
    expect($asset)->toBeNull();
});

it('fails to create buy order with insufficient USD balance', function () {
    $user = User::factory()->buyer('100.00000000')->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['balance_usd']);

    expect(Order::count())->toBe(0);

    $user->refresh();
    expect($user->balance_usd)->toBe('100.00000000');

    Queue::assertNothingPushed();
});

it('fails to create sell order with insufficient asset balance', function () {
    $user = User::factory()->create();
    Asset::factory()->for($user)->btc('0.001000000000000000')->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);

    expect(Order::count())->toBe(0);

    Queue::assertNothingPushed();
});

it('requires authentication to create order', function () {
    $response = $this->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertUnauthorized();
});

it('validates required fields for creating order', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
});

it('validates symbol must be BTC or ETH', function () {
    $user = User::factory()->buyer()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'INVALID',
        'side' => 'buy',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol']);
});

it('validates side must be buy or sell', function () {
    $user = User::factory()->buyer()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'invalid',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['side']);
});

it('validates price must be greater than zero', function () {
    $user = User::factory()->buyer()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '0',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '-1',
        'amount' => '0.010000000000000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});

it('validates amount must be greater than zero', function () {
    $user = User::factory()->buyer()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '95000.00000000',
        'amount' => '0',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '95000.00000000',
        'amount' => '-1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

it('cancels a buy order and refunds locked USD', function () {
    $user = User::factory()->buyer('100000.00000000')->create();
    $order = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($user)->create();

    $lockedUsdBefore = $order->locked_usd;
    $userBalanceBefore = $user->fresh()->balance_usd;

    $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.status', OrderStatus::CANCELLED->value);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CANCELLED);
    expect($order->cancelled_at)->not->toBeNull();
    expect($order->locked_usd)->toBe('0.00000000');

    $user->refresh();
    $expectedBalance = Money::add($userBalanceBefore, $lockedUsdBefore, Money::USD_SCALE);
    expect($user->balance_usd)->toBe($expectedBalance);
});

it('cancels a sell order and unlocks asset', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->for($user)->btc('1.000000000000000000')->create();
    $order = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($user)->create();

    $asset->refresh();
    expect($asset->amount)->toBe('0.990000000000000000');
    expect($asset->locked_amount)->toBe('0.010000000000000000');

    $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.status', OrderStatus::CANCELLED->value);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CANCELLED);

    $asset->refresh();
    expect($asset->amount)->toBe('1.000000000000000000');
    expect($asset->locked_amount)->toBe('0.000000000000000000');
});

it('fails to cancel a filled order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'side' => OrderSide::BUY->value,
        'status' => OrderStatus::FILLED->value,
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order']);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::FILLED);
});

it('fails to cancel an already cancelled order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'side' => OrderSide::BUY->value,
        'status' => OrderStatus::CANCELLED->value,
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
        'cancelled_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order']);
});

it('returns 404 when cancelling another users order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $order = Order::factory()->buyOpen()->for($user1)->create();

    $response = $this->actingAs($user2)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertNotFound();
});

it('returns 404 when cancelling non-existent order', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/orders/99999/cancel');

    $response->assertNotFound();
});

it('requires authentication to cancel order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->buyOpen()->for($user)->create();

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertUnauthorized();
});

it('prevents double spending when creating buy order with exact balance', function () {
    $user = User::factory()->buyer('1000.00000000')->create();

    $price = '95000.00000000';
    $amount = '0.010000000000000000';
    $totalNeeded = FeeCalculator::calculateTotal($price, $amount, Money::USD_SCALE);

    $user->balance_usd = $totalNeeded;
    $user->save();

    $response1 = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => $price,
        'amount' => $amount,
    ]);

    $response1->assertCreated();

    $response2 = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => $price,
        'amount' => $amount,
    ]);

    $response2->assertStatus(422)
        ->assertJsonValidationErrors(['balance_usd']);

    expect(Order::where('user_id', $user->id)->count())->toBe(1);
});

it('prevents double spending when creating sell order with exact asset amount', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->for($user)->btc('0.010000000000000000')->create();

    $response1 = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response1->assertCreated();

    $response2 = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
    ]);

    $response2->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);

    expect(Order::where('user_id', $user->id)->count())->toBe(1);
});
