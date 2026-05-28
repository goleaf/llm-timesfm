<?php

use App\Actions\Crypto\RunForecastAnalyzersAction;
use App\Models\CryptoAsset;
use App\Models\CryptoForecastPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores configured technical analyzers from real candle history', function (): void {
    config()->set('crypto.forecasting.timesfm.enabled', false);
    config()->set('crypto.forecasting.analyzers', ['trend', 'moving-average', 'ema', 'momentum']);

    $asset = CryptoAsset::factory()
        ->hasCandles(24, [
            'interval' => '1m',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
        ]);

    $forecasts = app(RunForecastAnalyzersAction::class)->handle($asset, '1m', 4, 24);

    expect($forecasts)->toHaveCount(4)
        ->and($forecasts->pluck('source')->sort()->values()->all())
        ->toBe(['ema', 'momentum', 'moving-average', 'trend'])
        ->and(CryptoForecastPoint::query()->count())
        ->toBe(16);
});
