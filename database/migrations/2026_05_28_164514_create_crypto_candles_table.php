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
        Schema::create('crypto_candles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('binance')->index();
            $table->string('interval', 8)->index();
            $table->timestampTz('open_time', 3)->index();
            $table->timestampTz('close_time', 3)->index();
            $table->decimal('open_price', 32, 12);
            $table->decimal('high_price', 32, 12);
            $table->decimal('low_price', 32, 12);
            $table->decimal('close_price', 32, 12);
            $table->decimal('base_volume', 32, 12)->default(0);
            $table->decimal('quote_volume', 32, 12)->default(0);
            $table->unsignedBigInteger('trade_count')->default(0);
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['crypto_asset_id', 'source', 'interval', 'open_time'], 'crypto_candle_unique_open');
            $table->index(['crypto_asset_id', 'interval', 'open_time'], 'crypto_candle_asset_interval_open_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_candles');
    }
};
