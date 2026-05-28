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
        Schema::table('crypto_assets', function (Blueprint $table) {
            $table->string('status', 32)->nullable()->after('quote_asset')->index();
            $table->unsignedSmallInteger('base_asset_precision')->nullable()->after('status');
            $table->unsignedSmallInteger('quote_asset_precision')->nullable()->after('base_asset_precision');
            $table->unsignedSmallInteger('quote_precision')->nullable()->after('quote_asset_precision');
            $table->boolean('is_spot_trading_allowed')->default(false)->after('quote_precision')->index();
            $table->boolean('is_margin_trading_allowed')->default(false)->after('is_spot_trading_allowed')->index();
            $table->json('order_types')->nullable()->after('is_margin_trading_allowed');
            $table->json('permissions')->nullable()->after('order_types');
            $table->json('permission_sets')->nullable()->after('permissions');
            $table->json('filters')->nullable()->after('permission_sets');
            $table->json('raw_payload')->nullable()->after('filters');
        });

        Schema::table('crypto_price_snapshots', function (Blueprint $table) {
            $table->timestampTz('open_time', 3)->nullable()->after('source_event_at')->index();
            $table->timestampTz('close_time', 3)->nullable()->after('open_time')->index();
            $table->decimal('price_change', 32, 12)->nullable()->after('price');
            $table->decimal('price_change_percent', 16, 8)->nullable()->after('price_change');
            $table->decimal('weighted_avg_price', 32, 12)->nullable()->after('price_change_percent');
            $table->decimal('prev_close_price', 32, 12)->nullable()->after('weighted_avg_price');
            $table->decimal('last_qty', 32, 12)->nullable()->after('prev_close_price');
            $table->decimal('bid_price', 32, 12)->nullable()->after('last_qty');
            $table->decimal('bid_qty', 32, 12)->nullable()->after('bid_price');
            $table->decimal('ask_price', 32, 12)->nullable()->after('bid_qty');
            $table->decimal('ask_qty', 32, 12)->nullable()->after('ask_price');
            $table->unsignedBigInteger('first_trade_id')->nullable()->after('trade_count');
            $table->unsignedBigInteger('last_trade_id')->nullable()->after('first_trade_id');
        });

        Schema::table('crypto_candles', function (Blueprint $table) {
            $table->decimal('taker_buy_base_volume', 32, 12)->default(0)->after('trade_count');
            $table->decimal('taker_buy_quote_volume', 32, 12)->default(0)->after('taker_buy_base_volume');
            $table->string('ignored_value', 64)->nullable()->after('taker_buy_quote_volume');

            $table->index(['crypto_asset_id', 'interval', 'close_time'], 'crypto_candle_asset_interval_close_index');
        });

        Schema::table('crypto_forecasts', function (Blueprint $table) {
            $table->timestampTz('input_starts_at', 3)->nullable()->after('completed_at')->index();
            $table->timestampTz('input_ends_at', 3)->nullable()->after('input_starts_at')->index();
            $table->timestampTz('target_starts_at', 3)->nullable()->after('input_ends_at')->index();
            $table->timestampTz('target_ends_at', 3)->nullable()->after('target_starts_at')->index();
            $table->decimal('base_price', 32, 12)->nullable()->after('target_ends_at');
            $table->unsignedSmallInteger('total_points')->default(0)->after('base_price');
            $table->unsignedSmallInteger('evaluated_points')->default(0)->after('total_points')->index();
            $table->decimal('mean_absolute_error', 32, 12)->nullable()->after('evaluated_points');
            $table->decimal('mean_absolute_percentage_error', 16, 8)->nullable()->after('mean_absolute_error');
            $table->decimal('direction_accuracy', 8, 4)->nullable()->after('mean_absolute_percentage_error');
            $table->timestampTz('evaluated_at', 3)->nullable()->after('direction_accuracy')->index();

            $table->index(['crypto_asset_id', 'interval', 'evaluated_at'], 'crypto_forecast_asset_interval_evaluated_index');
        });

        Schema::create('crypto_forecast_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crypto_forecast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crypto_asset_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('timesfm')->index();
            $table->string('interval', 8)->index();
            $table->unsignedSmallInteger('step');
            $table->timestampTz('target_open_time', 3)->index();
            $table->decimal('base_price', 32, 12)->nullable();
            $table->decimal('predicted_price', 32, 12);
            $table->decimal('quantile_low', 32, 12)->nullable();
            $table->decimal('quantile_median', 32, 12)->nullable();
            $table->decimal('quantile_high', 32, 12)->nullable();
            $table->decimal('actual_close_price', 32, 12)->nullable();
            $table->decimal('absolute_error', 32, 12)->nullable();
            $table->decimal('absolute_percentage_error', 16, 8)->nullable();
            $table->boolean('direction_correct')->nullable()->index();
            $table->timestampTz('evaluated_at', 3)->nullable()->index();
            $table->timestamps();

            $table->unique(['crypto_forecast_id', 'step'], 'crypto_forecast_point_unique_step');
            $table->index(['crypto_asset_id', 'interval', 'target_open_time'], 'crypto_forecast_point_asset_interval_target_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_forecast_points');

        Schema::table('crypto_forecasts', function (Blueprint $table) {
            $table->dropIndex('crypto_forecast_asset_interval_evaluated_index');
            $table->dropColumn([
                'input_starts_at',
                'input_ends_at',
                'target_starts_at',
                'target_ends_at',
                'base_price',
                'total_points',
                'evaluated_points',
                'mean_absolute_error',
                'mean_absolute_percentage_error',
                'direction_accuracy',
                'evaluated_at',
            ]);
        });

        Schema::table('crypto_candles', function (Blueprint $table) {
            $table->dropIndex('crypto_candle_asset_interval_close_index');
            $table->dropColumn([
                'taker_buy_base_volume',
                'taker_buy_quote_volume',
                'ignored_value',
            ]);
        });

        Schema::table('crypto_price_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'open_time',
                'close_time',
                'price_change',
                'price_change_percent',
                'weighted_avg_price',
                'prev_close_price',
                'last_qty',
                'bid_price',
                'bid_qty',
                'ask_price',
                'ask_qty',
                'first_trade_id',
                'last_trade_id',
            ]);
        });

        Schema::table('crypto_assets', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'base_asset_precision',
                'quote_asset_precision',
                'quote_precision',
                'is_spot_trading_allowed',
                'is_margin_trading_allowed',
                'order_types',
                'permissions',
                'permission_sets',
                'filters',
                'raw_payload',
            ]);
        });
    }
};
