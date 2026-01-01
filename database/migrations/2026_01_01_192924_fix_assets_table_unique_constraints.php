<?php

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
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign('assets_user_id_foreign');
            $table->dropUnique('assets_user_id_unique');
            $table->unique(['user_id', 'symbol'], 'assets_user_symbol_unique');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign('assets_user_id_foreign');
            $table->dropUnique('assets_user_symbol_unique');
            $table->unique('user_id', 'assets_user_id_unique');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
