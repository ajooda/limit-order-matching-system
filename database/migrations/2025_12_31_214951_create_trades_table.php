<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->index();
            $table->decimal('price', 20, 8);
            $table->decimal('amount', 36, 18);
            $table->decimal('usd_volume', 20, 8);
            $table->decimal('fee_usd', 20, 8);
            $table->foreignIdFor(Order::class, 'buy_order_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Order::class, 'sell_order_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'buyer_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'seller_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
