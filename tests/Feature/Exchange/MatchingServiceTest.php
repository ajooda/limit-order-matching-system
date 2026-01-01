<?php

use App\Domain\Exchange\Services\MatchingService;
use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatchedEvent;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Support\FeeCalculator;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([OrderMatchedEvent::class]);
});

it('matches buy and sell orders with same amount and compatible prices', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    $sellerAsset = Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    $buyerBalanceBefore = $buyer->fresh()->balance_usd;
    $sellerBalanceBefore = $seller->fresh()->balance_usd;
    $lockedUsd = $buyOrder->locked_usd;

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->symbol)->toBe('BTC');
    expect($trade->price)->toBe('95000.00000000');
    expect($trade->amount)->toBe('0.010000000000000000');
    expect($trade->buy_order_id)->toBe($buyOrder->id);
    expect($trade->sell_order_id)->toBe($sellOrder->id);
    expect($trade->buyer_id)->toBe($buyer->id);
    expect($trade->seller_id)->toBe($seller->id);

    $expectedVolume = Money::mul('95000.00000000', '0.010000000000000000', Money::USD_SCALE);
    $expectedFee = FeeCalculator::calculateFee($expectedVolume, Money::USD_SCALE);
    expect($trade->usd_volume)->toBe($expectedVolume);
    expect($trade->fee_usd)->toBe($expectedFee);

    $buyOrder->refresh();
    $sellOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatus::FILLED);
    expect($sellOrder->status)->toBe(OrderStatus::FILLED);
    expect($buyOrder->filled_at)->not->toBeNull();
    expect($sellOrder->filled_at)->not->toBeNull();
    expect($buyOrder->locked_usd)->toBe('0.00000000');

    $buyer->refresh();
    $actualTotal = FeeCalculator::calculateTotal('95000.00000000', '0.010000000000000000', Money::USD_SCALE);
    $refund = Money::sub($lockedUsd, $actualTotal, Money::USD_SCALE);
    if (Money::cmp($refund, '0', Money::USD_SCALE) > 0) {
        $expectedBuyerBalance = Money::add($buyerBalanceBefore, $refund, Money::USD_SCALE);
    } else {
        $expectedBuyerBalance = $buyerBalanceBefore; // No refund, balance already locked
    }
    expect($buyer->balance_usd)->toBe($expectedBuyerBalance);

    $seller->refresh();
    $expectedSellerBalance = Money::add($sellerBalanceBefore, $expectedVolume, Money::USD_SCALE);
    expect($seller->balance_usd)->toBe($expectedSellerBalance);

    $buyerAsset = $buyer->assets()->where('symbol', 'BTC')->first();
    expect($buyerAsset)->not->toBeNull();
    expect($buyerAsset->amount)->toBe('0.010000000000000000');
    expect($buyerAsset->locked_amount)->toBe('0.000000000000000000');

    $sellerAsset->refresh();
    expect($sellerAsset->amount)->toBe('0.990000000000000000');
    expect($sellerAsset->locked_amount)->toBe('0.000000000000000000');

    Event::assertDispatched(OrderMatchedEvent::class, 2);
    Event::assertDispatched(OrderMatchedEvent::class, function ($event) use ($buyer) {
        return $event->user->id === $buyer->id;
    });
    Event::assertDispatched(OrderMatchedEvent::class, function ($event) use ($seller) {
        return $event->user->id === $seller->id;
    });
});

it('matches when buy order price is higher than sell order price', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '96000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->price)->toBe('95000.00000000');
});

it('matches when sell order price is lower than buy order price', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '94000.00000000', '0.010000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->price)->toBe('94000.00000000'); // Counter order price
});

it('refunds excess locked USD to buyer when buy order price is higher', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '96000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    $buyerBalanceBefore = $buyer->fresh()->balance_usd;
    $lockedUsd = $buyOrder->locked_usd;

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    // Buyer should get refund of difference between locked amount and actual total
    $buyer->refresh();
    $actualTotal = FeeCalculator::calculateTotal('95000.00000000', '0.010000000000000000', Money::USD_SCALE);
    $expectedRefund = Money::sub($lockedUsd, $actualTotal, Money::USD_SCALE);
    $expectedBalance = Money::add($buyerBalanceBefore, $expectedRefund, Money::USD_SCALE);
    expect($buyer->balance_usd)->toBe($expectedBalance);
});

it('creates buyer asset wallet if it does not exist', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    expect($buyer->assets()->where('symbol', 'BTC')->count())->toBe(0);

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $buyerAsset = $buyer->assets()->where('symbol', 'BTC')->first();
    expect($buyerAsset)->not->toBeNull();
    expect($buyerAsset->amount)->toBe('0.010000000000000000');
});

it('returns null when no counter order exists', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    expect(Trade::count())->toBe(0);
    $buyOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatus::OPEN);

    Event::assertNothingDispatched();
});

it('returns null when order does not exist', function () {
    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection(99999);

    expect(Trade::count())->toBe(0);
    Event::assertNothingDispatched();
});

it('does not match orders with different symbols', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->eth('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('ETH', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('matches ETH orders correctly', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->eth('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('ETH', '3000.00000000', '0.100000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('ETH', '3000.00000000', '0.100000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->symbol)->toBe('ETH');
    Event::assertDispatched(OrderMatchedEvent::class, 2);
});

it('does not refund when locked USD exactly equals actual total', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $price = '95000.00000000';
    $amount = '0.010000000000000000';
    $buyOrder = Order::factory()->buyOpen('BTC', $price, $amount)->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', $price, $amount)->for($seller)->create();

    $buyerBalanceBefore = $buyer->fresh()->balance_usd;
    $actualTotal = FeeCalculator::calculateTotal($price, $amount, Money::USD_SCALE);

    expect($buyOrder->locked_usd)->toBe($actualTotal);

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $buyer->refresh();
    expect($buyer->balance_usd)->toBe($buyerBalanceBefore);
});

it('matches with order ID as tiebreaker when price and created_at are identical', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller1 = User::factory()->create();
    $seller2 = User::factory()->create();
    Asset::factory()->for($seller1)->btc('1.000000000000000000')->create();
    Asset::factory()->for($seller2)->btc('1.000000000000000000')->create();

    $now = now();
    $buyOrder = Order::factory()->buyOpen('BTC', '96000.00000000', '0.010000000000000000')->for($buyer)->create(['created_at' => $now]);
    $sellOrder1 = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller1)->create(['created_at' => $now]);
    $sellOrder2 = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller2)->create(['created_at' => $now]);

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->sell_order_id)->toBe($sellOrder1->id);
});

it('returns null when counter order has different amount', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.020000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('returns null when counter order price is not compatible', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '96000.00000000', '0.010000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('returns null when order is already filled', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->create([
        'user_id' => $buyer->id,
        'symbol' => 'BTC',
        'side' => OrderSide::BUY->value,
        'status' => OrderStatus::FILLED->value,
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
        'filled_at' => now(),
    ]);

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('returns null when order is cancelled', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $buyOrder = Order::factory()->create([
        'user_id' => $buyer->id,
        'symbol' => 'BTC',
        'side' => OrderSide::BUY->value,
        'status' => OrderStatus::CANCELLED->value,
        'price' => '95000.00000000',
        'amount' => '0.010000000000000000',
        'cancelled_at' => now(),
    ]);

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('matches with lowest price sell order when multiple sell orders exist', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller1 = User::factory()->create();
    $seller2 = User::factory()->create();
    Asset::factory()->for($seller1)->btc('1.000000000000000000')->create();
    Asset::factory()->for($seller2)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '96000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder1 = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller1)->create();
    $sellOrder2 = Order::factory()->sellOpen('BTC', '95500.00000000', '0.010000000000000000')->for($seller2)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->price)->toBe('95000.00000000');
    expect($trade->sell_order_id)->toBe($sellOrder1->id);
});

it('matches with highest price buy order when multiple buy orders exist', function () {
    $buyer1 = User::factory()->buyer('100000.00000000')->create();
    $buyer2 = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder1 = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer1)->create();
    $buyOrder2 = Order::factory()->buyOpen('BTC', '96000.00000000', '0.010000000000000000')->for($buyer2)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95500.00000000', '0.010000000000000000')->for($seller)->create();

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($sellOrder->id);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();
    expect($trade->price)->toBe('96000.00000000');
    expect($trade->buy_order_id)->toBe($buyOrder2->id);
});

it('throws exception when seller asset wallet not found', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    // Manually delete the asset to simulate missing wallet
    $seller->assets()->where('symbol', 'BTC')->delete();

    $matchingService = new MatchingService;

    expect(fn () => $matchingService->attemptMatchSelection($buyOrder->id))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('throws exception when seller locked amount is insufficient', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    $asset = Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    // Manually reduce locked amount to simulate data corruption
    $asset->locked_amount = '0.001000000000000000';
    $asset->save();

    $matchingService = new MatchingService;

    expect(fn () => $matchingService->attemptMatchSelection($buyOrder->id))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('throws exception when buyer locked USD is insufficient', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $buyOrder = Order::factory()->buyOpen('BTC', '95000.00000000', '0.010000000000000000')->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen('BTC', '95000.00000000', '0.010000000000000000')->for($seller)->create();

    // Manually reduce locked_usd to simulate data corruption
    $buyOrder->locked_usd = '100.00000000';
    $buyOrder->save();

    $matchingService = new MatchingService;

    expect(fn () => $matchingService->attemptMatchSelection($buyOrder->id))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Trade::count())->toBe(0);
    Event::assertNotDispatched(OrderMatchedEvent::class);
});

it('completes full order lifecycle: create buy order, create sell order, match, verify state', function () {
    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    Asset::factory()->for($seller)->btc('1.000000000000000000')->create();

    $orderService = app(\App\Domain\Exchange\Services\OrderService::class);
    $dto = new \App\Domain\Exchange\DTO\CreateOrderData(
        symbol: 'BTC',
        side: OrderSide::BUY,
        price: '95000.00000000',
        amount: '0.010000000000000000',
    );
    $buyOrder = $orderService->createOrder($buyer, $dto);

    $dto = new \App\Domain\Exchange\DTO\CreateOrderData(
        symbol: 'BTC',
        side: OrderSide::SELL,
        price: '95000.00000000',
        amount: '0.010000000000000000',
    );
    $sellOrder = $orderService->createOrder($seller, $dto);

    $matchingService = new MatchingService;
    $matchingService->attemptMatchSelection($buyOrder->id);

    $buyOrder->refresh();
    $sellOrder->refresh();
    expect($buyOrder->status)->toBe(OrderStatus::FILLED);
    expect($sellOrder->status)->toBe(OrderStatus::FILLED);

    $trade = Trade::first();
    expect($trade)->not->toBeNull();

    $buyer->refresh();
    $seller->refresh();
    expect($buyer->assets()->where('symbol', 'BTC')->first()->amount)->toBe('0.010000000000000000');
    expect($seller->assets()->where('symbol', 'BTC')->first()->amount)->toBe('0.990000000000000000');

    Event::assertDispatched(OrderMatchedEvent::class, 2);
});
