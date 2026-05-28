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
        Schema::create('crypto_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('binance')->index();
            $table->timestampTz('source_event_at', 3)->index();
            $table->decimal('price', 32, 12);
            $table->decimal('open_price', 32, 12);
            $table->decimal('high_price', 32, 12);
            $table->decimal('low_price', 32, 12);
            $table->decimal('base_volume', 32, 12)->default(0);
            $table->decimal('quote_volume', 32, 12)->default(0);
            $table->unsignedBigInteger('trade_count')->default(0);
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['crypto_asset_id', 'source', 'source_event_at'], 'crypto_snapshot_unique_event');
            $table->index(['crypto_asset_id', 'source_event_at'], 'crypto_snapshot_asset_event_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_price_snapshots');
    }
};
