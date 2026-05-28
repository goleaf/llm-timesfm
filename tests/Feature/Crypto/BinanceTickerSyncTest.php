<?php

use App\Actions\Crypto\FetchBinanceExchangeInfoAction;
use App\Actions\Crypto\FetchBinanceTickersAction;
use App\Models\CryptoAsset;
use App\Models\CryptoPriceSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('stores active binance ticker assets and their latest snapshots', function (): void {
    config()->set('crypto.binance.base_url', 'https://api.binance.test');
    config()->set('crypto.binance.symbols', ['BTCUSDT', 'ETHUSDT']);

    Http::fake([
        'api.binance.test/api/v3/ticker/24hr*' => Http::response([
            [
                'symbol' => 'BTCUSDT',
                'priceChange' => '1000.50000000',
                'priceChangePercent' => '1.429',
                'weightedAvgPrice' => '70500.00000000',
                'prevClosePrice' => '70000.00000000',
                'openPrice' => '70000.00000000',
                'highPrice' => '72000.00000000',
                'lowPrice' => '69000.00000000',
                'lastPrice' => '71000.50000000',
                'lastQty' => '0.01000000',
                'bidPrice' => '71000.49000000',
                'bidQty' => '1.50000000',
                'askPrice' => '71000.50000000',
                'askQty' => '2.50000000',
                'volume' => '120.25000000',
                'quoteVolume' => '8530560.12500000',
                'openTime' => 1779899957002,
                'closeTime' => 1779986357002,
                'firstId' => 100,
                'lastId' => 200,
                'count' => 1000,
            ],
            [
                'symbol' => 'ETHUSDT',
                'openPrice' => '2000.00000000',
                'highPrice' => '2050.00000000',
                'lowPrice' => '1980.00000000',
                'lastPrice' => '2030.25000000',
                'volume' => '900.50000000',
                'quoteVolume' => '1828272.62500000',
                'openTime' => 1779899957004,
                'closeTime' => 1779986357004,
                'count' => 2000,
            ],
        ]),
    ]);

    $summary = app(FetchBinanceTickersAction::class)->handle(['BTCUSDT', 'ETHUSDT']);

    expect($summary)->toMatchArray([
        'assets' => 2,
        'snapshots' => 2,
    ]);

    $asset = CryptoAsset::query()->forSymbol('BTCUSDT')->withLatestSnapshot()->firstOrFail();

    expect($asset->base_asset)->toBe('BTC')
        ->and($asset->quote_asset)->toBe('USDT')
        ->and($asset->latestSnapshot->price)->toEqual('71000.500000000000')
        ->and($asset->latestSnapshot->price_change_percent)->toEqual('1.42900000')
        ->and($asset->latestSnapshot->bid_price)->toEqual('71000.490000000000')
        ->and($asset->latestSnapshot->ask_qty)->toEqual('2.500000000000')
        ->and($asset->latestSnapshot->quote_volume)->toEqual('8530560.125000000000');

    expect(CryptoPriceSnapshot::query()->count())->toBe(2);
});

it('stores binance exchange metadata for configured assets', function (): void {
    config()->set('crypto.binance.base_url', 'https://api.binance.test');
    config()->set('crypto.binance.symbols', ['BTCUSDT']);

    Http::fake([
        'api.binance.test/api/v3/exchangeInfo*' => Http::response([
            'symbols' => [
                [
                    'symbol' => 'BTCUSDT',
                    'status' => 'TRADING',
                    'baseAsset' => 'BTC',
                    'baseAssetPrecision' => 8,
                    'quoteAsset' => 'USDT',
                    'quotePrecision' => 8,
                    'quoteAssetPrecision' => 8,
                    'orderTypes' => ['LIMIT', 'MARKET'],
                    'isSpotTradingAllowed' => true,
                    'isMarginTradingAllowed' => true,
                    'filters' => [
                        ['filterType' => 'PRICE_FILTER', 'tickSize' => '0.01000000'],
                    ],
                    'permissions' => [],
                    'permissionSets' => [['SPOT']],
                ],
            ],
        ]),
    ]);

    $summary = app(FetchBinanceExchangeInfoAction::class)->handle(['BTCUSDT']);
    $asset = CryptoAsset::query()->forSymbol('BTCUSDT')->firstOrFail();

    expect($summary)->toBe(['assets' => 1])
        ->and($asset->status)->toBe('TRADING')
        ->and($asset->base_asset_precision)->toBe(8)
        ->and($asset->is_spot_trading_allowed)->toBeTrue()
        ->and($asset->filters[0]['filterType'])->toBe('PRICE_FILTER');
});
