<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:18',
            'locked_amount' => 'decimal:18',

        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
