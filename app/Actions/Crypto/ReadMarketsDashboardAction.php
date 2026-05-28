<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoPriceSnapshot;
use App\Services\Crypto\CryptoCache;
use Illuminate\Support\Collection;

class ReadMarketsDashboardAction
{
    public function __construct(
        private readonly CryptoCache $cache,
    ) {}

    /**
     * @return array{
     *     assets:Collection<int,CryptoAsset>,
     *     selectedAsset:?CryptoAsset,
     *     candles:Collection<int,CryptoCandle>,
     *     snapshots:Collection<int,CryptoPriceSnapshot>,
     *     forecast:?CryptoForecast
     * }
     */
    public function handle(string $selectedSymbol, string $interval): array
    {
        $limit = (int) config('crypto.binance.market_limit', 20);
        $symbol = strtoupper($selectedSymbol);
        $assets = $this->assets($limit);
        $selectedAsset = $assets->firstWhere('symbol', $symbol)
            ?: $this->selectedAsset($symbol)
            ?: $assets->first();

        return [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'candles' => $selectedAsset ? $this->candles($selectedAsset, $interval) : collect(),
            'snapshots' => $selectedAsset ? $this->snapshots($selectedAsset) : collect(),
            'forecast' => $selectedAsset ? $this->latestForecast($selectedAsset, $interval) : null,
        ];
    }

    /**
     * @return Collection<int, CryptoAsset>
     */
    private function assets(int $limit): Collection
    {
        return $this->cache->remember(
            "markets:assets:{$limit}",
            'assets',
            fn () => CryptoAsset::query()->dashboardList($limit)->get(),
        );
    }

    private function selectedAsset(string $symbol): ?CryptoAsset
    {
        return $this->cache->remember(
            "markets:selected-asset:{$symbol}",
            'selected_asset',
            fn () => CryptoAsset::query()
                ->forSymbol($symbol)
                ->withLatestSnapshot()
                ->first(),
        );
    }

    /**
     * @return Collection<int, CryptoCandle>
     */
    private function candles(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->remember(
            "markets:candles:{$asset->getKey()}:{$interval}:160",
            'market_history',
            fn () => CryptoCandle::query()
                ->select([
                    'id',
                    'crypto_asset_id',
                    'source',
                    'interval',
                    'open_time',
                    'close_time',
                    'open_price',
                    'high_price',
                    'low_price',
                    'close_price',
                    'base_volume',
                    'quote_volume',
                    'trade_count',
                    'created_at',
                    'updated_at',
                ])
                ->forAsset($asset)
                ->forInterval($interval)
                ->latestComplete()
                ->limit(160)
                ->get()
                ->sortBy('open_time')
                ->values(),
        );
    }

    /**
     * @return Collection<int, CryptoPriceSnapshot>
     */
    private function snapshots(CryptoAsset $asset): Collection
    {
        return $this->cache->remember(
            "markets:snapshots:{$asset->getKey()}:20",
            'snapshots',
            fn () => CryptoPriceSnapshot::query()
                ->forAsset($asset)
                ->latestEvents()
                ->limit(20)
                ->get(),
        );
    }

    private function latestForecast(CryptoAsset $asset, string $interval): ?CryptoForecast
    {
        return $this->cache->remember(
            "markets:forecast:{$asset->getKey()}:{$interval}",
            'latest_forecast',
            fn () => CryptoForecast::query()
                ->forAsset($asset)
                ->forInterval($interval)
                ->latestCompleted()
                ->first(),
        );
    }
}
