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
        Schema::table('crypto_assets', function (Blueprint $table): void {
            $table->index(['is_active', 'rank', 'sort_quote_volume', 'id'], 'crypto_assets_active_rank_volume_id_index');
            $table->index(['quote_asset', 'is_active', 'rank'], 'crypto_assets_quote_active_rank_index');
        });

        Schema::table('crypto_price_snapshots', function (Blueprint $table): void {
            $table->index(['crypto_asset_id', 'source', 'source_event_at', 'id'], 'crypto_snapshots_asset_source_event_id_index');
        });

        Schema::table('crypto_candles', function (Blueprint $table): void {
            $table->index(['interval', 'open_time', 'crypto_asset_id'], 'crypto_candles_interval_open_asset_index');
        });

        Schema::table('crypto_forecasts', function (Blueprint $table): void {
            $table->index(['crypto_asset_id', 'interval', 'status', 'completed_at', 'id'], 'crypto_forecasts_asset_interval_status_completed_id_index');
        });

        Schema::table('crypto_forecast_points', function (Blueprint $table): void {
            $table->index(['evaluated_at', 'target_open_time', 'id'], 'crypto_forecast_points_eval_target_id_index');
            $table->index(['crypto_asset_id', 'interval', 'evaluated_at', 'target_open_time', 'id'], 'crypto_forecast_points_asset_interval_eval_target_id_index');
            $table->index(['crypto_forecast_id', 'evaluated_at'], 'crypto_forecast_points_forecast_eval_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_forecast_points', function (Blueprint $table): void {
            $table->dropIndex('crypto_forecast_points_forecast_eval_index');
            $table->dropIndex('crypto_forecast_points_asset_interval_eval_target_id_index');
            $table->dropIndex('crypto_forecast_points_eval_target_id_index');
        });

        Schema::table('crypto_forecasts', function (Blueprint $table): void {
            $table->dropIndex('crypto_forecasts_asset_interval_status_completed_id_index');
        });

        Schema::table('crypto_candles', function (Blueprint $table): void {
            $table->dropIndex('crypto_candles_interval_open_asset_index');
        });

        Schema::table('crypto_price_snapshots', function (Blueprint $table): void {
            $table->dropIndex('crypto_snapshots_asset_source_event_id_index');
        });

        Schema::table('crypto_assets', function (Blueprint $table): void {
            $table->dropIndex('crypto_assets_quote_active_rank_index');
            $table->dropIndex('crypto_assets_active_rank_volume_id_index');
        });
    }
};
