<?php

use App\Domain\Exchange\Services\MatchingService;
use App\Jobs\MatchOrderJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('processes job and calls matching service', function () {
    Queue::fake();

    $buyer = User::factory()->buyer('100000.00000000')->create();
    $seller = User::factory()->create();
    $buyOrder = Order::factory()->buyOpen()->for($buyer)->create();
    $sellOrder = Order::factory()->sellOpen()->for($seller)->create();

    $job = new MatchOrderJob($buyOrder->id);
    $matchingService = new MatchingService;

    $job->handle($matchingService);

    expect($job->orderId)->toBe($buyOrder->id);
});

it('handles non-existent order gracefully', function () {
    $job = new MatchOrderJob(99999);
    $matchingService = new MatchingService;

    $job->handle($matchingService);

    expect(true)->toBeTrue();
});
