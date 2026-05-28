<?php

use App\Actions\Crypto\ReadMarketsDashboardAction;
use App\Actions\Crypto\WarmCryptoDashboardCacheAction;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoPriceSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('crypto.cache.enabled', true);
    config()->set('crypto.cache.store', 'array');
    Cache::store('array')->flush();
});

it('adds composite indexes for realtime market and forecast queries', function (): void {
    $assetIndexes = collect(Schema::getIndexes('crypto_assets'))->keyBy('name');
    $snapshotIndexes = collect(Schema::getIndexes('crypto_price_snapshots'))->keyBy('name');
    $candleIndexes = collect(Schema::getIndexes('crypto_candles'))->keyBy('name');
    $forecastIndexes = collect(Schema::getIndexes('crypto_forecasts'))->keyBy('name');
    $pointIndexes = collect(Schema::getIndexes('crypto_forecast_points'))->keyBy('name');
    $stakeIndexes = collect(Schema::getIndexes('crypto_prediction_stakes'))->keyBy('name');

    expect($assetIndexes->get('crypto_assets_active_rank_volume_id_index')['columns'])
        ->toBe(['is_active', 'rank', 'sort_quote_volume', 'id'])
        ->and($snapshotIndexes->get('crypto_snapshots_asset_source_event_id_index')['columns'])
        ->toBe(['crypto_asset_id', 'source', 'source_event_at', 'id'])
        ->and($candleIndexes->get('crypto_candles_interval_open_asset_index')['columns'])
        ->toBe(['interval', 'open_time', 'crypto_asset_id'])
        ->and($forecastIndexes->get('crypto_forecasts_asset_interval_status_completed_id_index')['columns'])
        ->toBe(['crypto_asset_id', 'interval', 'status', 'completed_at', 'id'])
        ->and($pointIndexes->get('crypto_forecast_points_eval_target_id_index')['columns'])
        ->toBe(['evaluated_at', 'target_open_time', 'id'])
        ->and($pointIndexes->get('crypto_forecast_points_asset_interval_eval_target_id_index')['columns'])
        ->toBe(['crypto_asset_id', 'interval', 'evaluated_at', 'target_open_time', 'id'])
        ->and($pointIndexes->get('crypto_forecast_points_forecast_eval_index')['columns'])
        ->toBe(['crypto_forecast_id', 'evaluated_at'])
        ->and($stakeIndexes->get('crypto_prediction_stakes_asset_interval_status_target_index')['columns'])
        ->toBe(['crypto_asset_id', 'interval', 'status', 'target_at', 'id'])
        ->and($stakeIndexes->get('crypto_prediction_stakes_asset_interval_target_index')['columns'])
        ->toBe(['crypto_asset_id', 'interval', 'target_at', 'id']);
});

it('serves repeated market dashboard reads from cache and invalidates on new market data', function (): void {
    $asset = CryptoAsset::factory()
        ->hasSnapshots(1, [
            'source_event_at' => CarbonImmutable::parse('2026-05-28 12:00:00 UTC'),
            'price' => '100.000000000000',
            'raw_payload' => ['symbol' => 'BTCUSDT', 'lastPrice' => '100.00000000'],
        ])
        ->hasCandles(3, [
            'interval' => '1m',
            'close_price' => '100.000000000000',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
            'rank' => 1,
        ]);

    CryptoForecast::factory()->create([
        'crypto_asset_id' => $asset->getKey(),
        'interval' => '1m',
    ]);

    $reader = app(ReadMarketsDashboardAction::class);

    DB::enableQueryLog();
    DB::flushQueryLog();
    $first = $reader->handle('BTCUSDT', '1m');
    $firstQueryCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $second = $reader->handle('BTCUSDT', '1m');
    $secondQueryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($first['selectedAsset']?->latestSnapshot?->price)->toEqual('100.000000000000')
        ->and($second['selectedAsset']?->latestSnapshot?->price)->toEqual('100.000000000000')
        ->and($firstQueryCount)->toBeGreaterThan(0)
        ->and($secondQueryCount)->toBeLessThan($firstQueryCount);

    CryptoPriceSnapshot::factory()->create([
        'crypto_asset_id' => $asset->getKey(),
        'source_event_at' => CarbonImmutable::parse('2026-05-28 12:01:00 UTC'),
        'price' => '111.000000000000',
        'raw_payload' => ['symbol' => 'BTCUSDT', 'lastPrice' => '111.00000000'],
    ]);

    $fresh = $reader->handle('BTCUSDT', '1m');

    expect($fresh['selectedAsset']?->latestSnapshot?->price)->toEqual('111.000000000000');
});

it('refreshes stale collection cache entries with the wrong shape', function (): void {
    $asset = CryptoAsset::factory()
        ->hasCandles(3, [
            'interval' => '1m',
            'close_price' => '100.000000000000',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
            'rank' => 1,
        ]);
    $cacheKey = "crypto:v1:markets:candles:{$asset->getKey()}:1m:160";

    Cache::store('array')->forever('crypto:data-version', 1);
    Cache::store('array')->put($cacheKey, 'stale-wrong-value', 60);

    $dashboard = app(ReadMarketsDashboardAction::class)->handle('BTCUSDT', '1m');

    expect($dashboard['candles'])
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and(Cache::store('array')->get($cacheKey))
        ->toBeInstanceOf(Collection::class);
});

it('warms market and forecast dashboard cache for configured realtime symbols', function (): void {
    CryptoAsset::factory()
        ->hasSnapshots(1, [
            'source_event_at' => CarbonImmutable::parse('2026-05-28 12:00:00 UTC'),
            'price' => '100.000000000000',
        ])
        ->hasCandles(3, [
            'interval' => '1m',
            'close_price' => '100.000000000000',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
            'rank' => 1,
        ]);

    $summary = app(WarmCryptoDashboardCacheAction::class)->handle(['BTCUSDT'], ['1m'], 1);

    expect($summary)->toBe([
        'symbols' => 1,
        'intervals' => 1,
        'reads' => 2,
    ]);
});
