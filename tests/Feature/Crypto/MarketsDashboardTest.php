<?php

use App\Livewire\ForecastStatsDashboard;
use App\Livewire\MarketsDashboard;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the realtime markets dashboard from stored market data', function (): void {
    $asset = CryptoAsset::factory()
        ->hasSnapshots(1, [
            'price' => '71000.500000000000',
            'open_price' => '70000.000000000000',
            'high_price' => '72000.000000000000',
            'low_price' => '69000.000000000000',
            'base_volume' => '120.250000000000',
            'quote_volume' => '8530560.125000000000',
            'trade_count' => 1000,
        ])
        ->hasCandles(3, [
            'interval' => '1m',
            'open_price' => '70000.000000000000',
            'high_price' => '72000.000000000000',
            'low_price' => '69000.000000000000',
            'close_price' => '71000.500000000000',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
            'rank' => 1,
        ]);

    get('/markets')
        ->assertOk()
        ->assertSee('Crypto Forecast')
        ->assertSee('BTC/USDT')
        ->assertSee('wire:poll.1000ms', false)
        ->assertSee('data-interactive-chart', false)
        ->assertSee('data-chart-payload', false)
        ->assertSee('Live price', false)
        ->assertSee('Candle close', false);

    Livewire::test(MarketsDashboard::class)
        ->call('selectAsset', $asset->symbol)
        ->assertSet('selectedSymbol', 'BTCUSDT')
        ->assertSee('71,000.50')
        ->assertSee('JSON History');
});

it('renders realtime forecast statistics from evaluated forecast points', function (): void {
    $asset = CryptoAsset::factory()
        ->hasSnapshots(1, [
            'price' => '71000.500000000000',
            'open_price' => '70000.000000000000',
            'high_price' => '72000.000000000000',
            'low_price' => '69000.000000000000',
            'base_volume' => '120.250000000000',
            'quote_volume' => '8530560.125000000000',
            'trade_count' => 1000,
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
            'rank' => 1,
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
        'evaluated_points' => 1,
        'mean_absolute_error' => '2.000000000000',
        'mean_absolute_percentage_error' => '1.851851850000',
        'direction_accuracy' => '100.0000',
        'evaluated_at' => now(),
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
        'actual_close_price' => '108.000000000000',
        'absolute_error' => '2.000000000000',
        'absolute_percentage_error' => '1.851851850000',
        'direction_correct' => true,
        'evaluated_at' => now(),
    ]);

    get('/markets/stats/BTCUSDT')
        ->assertOk()
        ->assertSee('Prediction Statistics')
        ->assertSee('BTC/USDT')
        ->assertSee('data-interactive-chart', false)
        ->assertSee('data-chart-payload', false)
        ->assertSee('Predicted', false)
        ->assertSee('Forecast error', false);

    Livewire::test(ForecastStatsDashboard::class, ['symbol' => 'BTCUSDT'])
        ->assertSet('selectedSymbol', 'BTCUSDT')
        ->assertSee('MAPE')
        ->assertSee('100.00%');
});
