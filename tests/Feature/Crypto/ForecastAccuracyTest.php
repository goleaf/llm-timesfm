<?php

use App\Actions\Crypto\EvaluateForecastAccuracyAction;
use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('evaluates stored forecast points against actual candle closes', function (): void {
    Carbon::setTestNow(CarbonImmutable::parse('2026-05-28 12:10:00 UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-28 12:10:00 UTC'));

    $asset = CryptoAsset::factory()->create([
        'symbol' => 'BTCUSDT',
        'base_asset' => 'BTC',
        'quote_asset' => 'USDT',
    ]);
    $targetOpenTime = CarbonImmutable::parse('2026-05-28 12:00:00 UTC');
    $forecast = CryptoForecast::query()->create([
        'crypto_asset_id' => $asset->getKey(),
        'source' => 'timesfm',
        'interval' => '1m',
        'context_points' => 120,
        'horizon' => 1,
        'status' => 'completed',
        'started_at' => now()->subSeconds(5),
        'completed_at' => now()->subSeconds(2),
        'input_starts_at' => $targetOpenTime->subMinutes(120),
        'input_ends_at' => $targetOpenTime->subMinute(),
        'target_starts_at' => $targetOpenTime,
        'target_ends_at' => $targetOpenTime,
        'base_price' => '100.000000000000',
        'total_points' => 1,
        'point_forecast' => [110.0],
        'config' => ['engine' => 'timesfm'],
    ]);

    CryptoForecastPoint::query()->create([
        'crypto_forecast_id' => $forecast->getKey(),
        'crypto_asset_id' => $asset->getKey(),
        'source' => 'timesfm',
        'interval' => '1m',
        'step' => 1,
        'target_open_time' => $targetOpenTime,
        'base_price' => '100.000000000000',
        'predicted_price' => '110.000000000000',
    ]);

    CryptoCandle::query()->create([
        'crypto_asset_id' => $asset->getKey(),
        'source' => 'binance',
        'interval' => '1m',
        'open_time' => $targetOpenTime,
        'close_time' => $targetOpenTime->addMinute()->subMillisecond(),
        'open_price' => '100.000000000000',
        'high_price' => '112.000000000000',
        'low_price' => '99.000000000000',
        'close_price' => '108.000000000000',
        'base_volume' => '1.000000000000',
        'quote_volume' => '108.000000000000',
        'trade_count' => 10,
        'raw_payload' => [],
    ]);

    $summary = app(EvaluateForecastAccuracyAction::class)->handle();
    $point = CryptoForecastPoint::query()->firstOrFail();
    $forecast->refresh();

    expect($summary)->toBe(['points' => 1, 'forecasts' => 1])
        ->and($point->actual_close_price)->toEqual('108.000000000000')
        ->and($point->absolute_error)->toEqual('2.000000000000')
        ->and($point->direction_correct)->toBeTrue()
        ->and($forecast->evaluated_points)->toBe(1)
        ->and($forecast->mean_absolute_percentage_error)->toEqual('1.85185185');

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});
