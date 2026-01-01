<?php

namespace App\Models;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'side' => OrderSide::class,
            'status' => OrderStatus::class,
            'price' => 'decimal:8',
            'amount' => 'decimal:18',
            'locked_usd' => 'decimal:8',
            'filled_at' => 'datetime',
            'cancelled_at' => 'datetime',

        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    public function isOpenOrder(): bool
    {
        return $this->status === OrderStatus::OPEN;
    }
}
