<?php

use App\Actions\Crypto\RunTimesFmForecastAction;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

it('stores a forecast returned by the TimesFM bridge process', function (): void {
    config()->set('crypto.forecasting.timesfm.enabled', true);
    config()->set('crypto.forecasting.timesfm.python', 'python3');
    config()->set('crypto.forecasting.timesfm.script', base_path('python/timesfm_forecast.py'));

    $asset = CryptoAsset::factory()
        ->hasCandles(12, [
            'interval' => '1m',
            'close_price' => '100.000000000000',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
        ]);

    Process::fake([
        '*' => Process::result(
            output: json_encode([
                'point_forecast' => [101.0, 102.0, 103.0],
                'quantile_forecast' => [
                    [100.0, 101.0, 102.0],
                    [101.0, 102.0, 103.0],
                    [102.0, 103.0, 104.0],
                ],
            ], JSON_THROW_ON_ERROR),
        ),
    ]);

    $forecast = app(RunTimesFmForecastAction::class)->handle($asset, '1m', 3, 12);

    expect($forecast)->toBeInstanceOf(CryptoForecast::class)
        ->and($forecast->status)->toBe('completed')
        ->and($forecast->point_forecast)->toEqual([101.0, 102.0, 103.0])
        ->and($forecast->total_points)->toBe(3)
        ->and(CryptoForecastPoint::query()->where('crypto_forecast_id', $forecast->getKey())->count())->toBe(3);
});
