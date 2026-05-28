<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoPredictionStake;
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
     *     forecast:?CryptoForecast,
     *     forecasts:Collection<int,CryptoForecast>,
     *     predictionStakes:Collection<int,CryptoPredictionStake>
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
        $forecasts = $selectedAsset ? $this->latestForecasts($selectedAsset, $interval) : collect();

        return [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'candles' => $selectedAsset ? $this->candles($selectedAsset, $interval) : collect(),
            'snapshots' => $selectedAsset ? $this->snapshots($selectedAsset) : collect(),
            'forecast' => $forecasts->first(),
            'forecasts' => $forecasts,
            'predictionStakes' => $selectedAsset ? $this->predictionStakes($selectedAsset, $interval) : collect(),
        ];
    }

    /**
     * @return Collection<int, CryptoAsset>
     */
    private function assets(int $limit): Collection
    {
        return $this->cache->rememberCollection(
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
        return $this->cache->rememberCollection(
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
        return $this->cache->rememberCollection(
            "markets:snapshots:{$asset->getKey()}:20",
            'snapshots',
            fn () => CryptoPriceSnapshot::query()
                ->forAsset($asset)
                ->latestEvents()
                ->limit(20)
                ->get(),
        );
    }

    /**
     * @return Collection<int, CryptoForecast>
     */
    private function latestForecasts(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "markets:forecasts:{$asset->getKey()}:{$interval}:12",
            'latest_forecast',
            fn () => CryptoForecast::query()
                ->forAsset($asset)
                ->forInterval($interval)
                ->latestCompleted()
                ->limit(12)
                ->get()
                ->unique('source')
                ->values(),
        )->take(12)->values();
    }

    /**
     * @return Collection<int, CryptoPredictionStake>
     */
    private function predictionStakes(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "markets:prediction-stakes:{$asset->getKey()}:{$interval}:12",
            'prediction_stakes',
            fn () => CryptoPredictionStake::query()
                ->dashboardList($asset, $interval, 12)
                ->get(),
        );
    }
}
