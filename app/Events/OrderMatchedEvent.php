<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatchedEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Trade $trade, public User $user) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->user->id}")];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    public function broadcastWith(): array
    {

        return [
            'trade' => $this->trade,
            'user' => $this->mapUser(),
            'orders' => $this->getOrders(),
        ];
    }

    private function getOrders(): array
    {
        return [
            $this->trade->buy_order_id => OrderStatus::FILLED->value,
            $this->trade->sell_order_id => OrderStatus::FILLED->value,
        ];

    }

    private function mapUser(): array
    {
        return [
            'balance_usd' => (string) $this->user->balance_usd,
            'assets' => $this->user->assets->map(fn ($a) => [
                'symbol' => $a->symbol,
                'amount' => (string) $a->amount,
                'locked_amount' => (string) $a->locked_amount])
                ->values()
                ->all(),
        ];
    }
}
