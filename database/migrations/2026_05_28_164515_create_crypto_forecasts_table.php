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
        Schema::create('crypto_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('timesfm')->index();
            $table->string('interval', 8)->index();
            $table->unsignedSmallInteger('context_points');
            $table->unsignedSmallInteger('horizon');
            $table->string('status', 24)->default('pending')->index();
            $table->timestampTz('started_at', 3)->nullable();
            $table->timestampTz('completed_at', 3)->nullable()->index();
            $table->json('point_forecast')->nullable();
            $table->json('quantile_forecast')->nullable();
            $table->json('config')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['crypto_asset_id', 'interval', 'completed_at'], 'crypto_forecast_asset_interval_completed_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_forecasts');
    }
};
