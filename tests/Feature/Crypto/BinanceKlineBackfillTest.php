<?php

use App\Actions\Crypto\FetchBinanceKlinesAction;
use App\Actions\Crypto\FillMissingCryptoCandlesAction;
use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('stores kline history for a selected crypto asset', function (): void {
    config()->set('crypto.binance.base_url', 'https://api.binance.test');

    $asset = CryptoAsset::factory()->create([
        'symbol' => 'BTCUSDT',
        'base_asset' => 'BTC',
        'quote_asset' => 'USDT',
    ]);

    Http::fake([
        'api.binance.test/api/v3/klines*' => Http::response([
            [
                1779986100000,
                '73229.06000000',
                '73249.88000000',
                '73199.05000000',
                '73199.05000000',
                '11.18024000',
                1779986159999,
                '818659.43344220',
                2893,
                '6.34860000',
                '464839.03032360',
                '0',
            ],
            [
                1779986160000,
                '73199.05000000',
                '73203.69000000',
                '73188.00000000',
                '73202.55000000',
                '9.62056000',
                1779986219999,
                '704191.77056820',
                2145,
                '6.23881000',
                '456658.15199920',
                '0',
            ],
        ]),
    ]);

    $stored = app(FetchBinanceKlinesAction::class)->handle($asset, '1m', 2);
    $storedAgain = app(FetchBinanceKlinesAction::class)->handle($asset, '1m', 2);

    expect($stored)->toBe(2)
        ->and($storedAgain)->toBe(2);
    expect(CryptoCandle::query()->forAsset($asset)->forInterval('1m')->count())->toBe(2);

    $latest = CryptoCandle::query()
        ->forAsset($asset)
        ->forInterval('1m')
        ->latestComplete()
        ->firstOrFail();

    expect($latest->close_price)->toEqual('73202.550000000000')
        ->and($latest->trade_count)->toBe(2145);
});

it('fills only missing recent short interval candles', function (): void {
    config()->set('crypto.binance.base_url', 'https://api.binance.test');
    config()->set('crypto.binance.short_intervals', ['1m']);

    Carbon::setTestNow(CarbonImmutable::parse('2026-05-28 12:05:20 UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-28 12:05:20 UTC'));

    $asset = CryptoAsset::factory()->create([
        'symbol' => 'BTCUSDT',
        'base_asset' => 'BTC',
        'quote_asset' => 'USDT',
    ]);

    foreach (['2026-05-28 12:00:00 UTC', '2026-05-28 12:01:00 UTC'] as $openTime) {
        $open = CarbonImmutable::parse($openTime);
        CryptoCandle::query()->create([
            'crypto_asset_id' => $asset->getKey(),
            'source' => 'binance',
            'interval' => '1m',
            'open_time' => $open,
            'close_time' => $open->addMinute()->subMillisecond(),
            'open_price' => '100.000000000000',
            'high_price' => '105.000000000000',
            'low_price' => '95.000000000000',
            'close_price' => '101.000000000000',
            'base_volume' => '10.000000000000',
            'quote_volume' => '1010.000000000000',
            'trade_count' => 100,
            'raw_payload' => [],
        ]);
    }

    Http::fake([
        'api.binance.test/api/v3/klines*' => Http::response([
            [
                1779969720000,
                '101.00000000',
                '102.00000000',
                '100.00000000',
                '101.50000000',
                '1.00000000',
                1779969779999,
                '101.50000000',
                10,
                '0.50000000',
                '50.75000000',
                '0',
            ],
            [
                1779969780000,
                '101.50000000',
                '103.00000000',
                '101.00000000',
                '102.50000000',
                '1.00000000',
                1779969839999,
                '102.50000000',
                10,
                '0.50000000',
                '51.25000000',
                '0',
            ],
            [
                1779969840000,
                '102.50000000',
                '104.00000000',
                '102.00000000',
                '103.50000000',
                '1.00000000',
                1779969899999,
                '103.50000000',
                10,
                '0.50000000',
                '51.75000000',
                '0',
            ],
        ]),
    ]);

    $summary = app(FillMissingCryptoCandlesAction::class)->handle(['BTCUSDT'], ['1m'], 5);

    Http::assertSent(fn ($request): bool => str_contains(
        $request->url(),
        'startTime='.(CarbonImmutable::parse('2026-05-28 12:02:00 UTC')->getTimestamp() * 1000),
    ));

    expect($summary['candles'])->toBe(3)
        ->and(CryptoCandle::query()->forAsset($asset)->forInterval('1m')->count())->toBe(5);

    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
});
