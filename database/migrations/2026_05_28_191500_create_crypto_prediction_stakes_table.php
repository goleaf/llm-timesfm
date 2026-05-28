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
        Schema::create('crypto_prediction_stakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('manual')->index();
            $table->string('interval', 8)->index();
            $table->string('direction', 12)->index();
            $table->timestampTz('target_at', 3)->index();
            $table->decimal('target_price', 32, 12);
            $table->unsignedTinyInteger('confidence')->default(50)->index();
            $table->decimal('entry_price', 32, 12)->nullable();
            $table->decimal('actual_price', 32, 12)->nullable();
            $table->decimal('price_delta', 32, 12)->nullable();
            $table->decimal('absolute_error', 32, 12)->nullable();
            $table->decimal('absolute_percentage_error', 16, 8)->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->boolean('direction_correct')->nullable()->index();
            $table->timestampTz('resolved_at', 3)->nullable()->index();
            $table->string('note', 160)->nullable();
            $table->timestamps();

            $table->index(['crypto_asset_id', 'interval', 'status', 'target_at', 'id'], 'crypto_prediction_stakes_asset_interval_status_target_index');
            $table->index(['crypto_asset_id', 'interval', 'target_at', 'id'], 'crypto_prediction_stakes_asset_interval_target_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_prediction_stakes');
    }
};
