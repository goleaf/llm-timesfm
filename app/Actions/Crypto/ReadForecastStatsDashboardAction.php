<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use App\Services\Crypto\CryptoCache;
use Illuminate\Support\Collection;

class ReadForecastStatsDashboardAction
{
    public function __construct(
        private readonly CryptoCache $cache,
    ) {}

    /**
     * @return array{
     *     assets:Collection<int,CryptoAsset>,
     *     selectedAsset:?CryptoAsset,
     *     forecasts:Collection<int,CryptoForecast>,
     *     points:Collection<int,CryptoForecastPoint>,
     *     pendingPoints:int,
     *     metrics:array{forecasts:int,evaluated_points:int,pending_points:int,mape:?float,mae:?float,direction_accuracy:?float}
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
        $forecasts = $selectedAsset ? $this->forecasts($selectedAsset, $interval) : collect();
        $points = $selectedAsset ? $this->points($selectedAsset, $interval) : collect();
        $pendingPoints = $selectedAsset ? $this->pendingPoints($selectedAsset, $interval) : 0;

        return [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'forecasts' => $forecasts,
            'points' => $points,
            'pendingPoints' => $pendingPoints,
            'metrics' => $this->metrics($forecasts, $pendingPoints),
        ];
    }

    /**
     * @return Collection<int, CryptoAsset>
     */
    private function assets(int $limit): Collection
    {
        return $this->cache->rememberCollection(
            "stats:assets:{$limit}",
            'assets',
            fn () => CryptoAsset::query()->dashboardList($limit)->get(),
        );
    }

    private function selectedAsset(string $symbol): ?CryptoAsset
    {
        return $this->cache->remember(
            "stats:selected-asset:{$symbol}",
            'selected_asset',
            fn () => CryptoAsset::query()
                ->forSymbol($symbol)
                ->withLatestSnapshot()
                ->first(),
        );
    }

    /**
     * @return Collection<int, CryptoForecast>
     */
    private function forecasts(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "stats:forecasts:{$asset->getKey()}:{$interval}:12",
            'forecast_stats',
            fn () => CryptoForecast::query()
                ->select([
                    'id',
                    'crypto_asset_id',
                    'source',
                    'interval',
                    'context_points',
                    'horizon',
                    'status',
                    'completed_at',
                    'total_points',
                    'evaluated_points',
                    'mean_absolute_error',
                    'mean_absolute_percentage_error',
                    'direction_accuracy',
                    'evaluated_at',
                    'created_at',
                    'updated_at',
                ])
                ->forAsset($asset)
                ->forInterval($interval)
                ->completed()
                ->orderByDesc('completed_at')
                ->limit(12)
                ->get(),
        );
    }

    /**
     * @return Collection<int, CryptoForecastPoint>
     */
    private function points(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "stats:points:{$asset->getKey()}:{$interval}:160",
            'forecast_stats',
            fn () => CryptoForecastPoint::query()
                ->select([
                    'id',
                    'crypto_forecast_id',
                    'crypto_asset_id',
                    'source',
                    'interval',
                    'step',
                    'target_open_time',
                    'base_price',
                    'predicted_price',
                    'actual_close_price',
                    'absolute_error',
                    'absolute_percentage_error',
                    'direction_correct',
                    'evaluated_at',
                    'created_at',
                    'updated_at',
                ])
                ->forAsset($asset)
                ->forInterval($interval)
                ->evaluated()
                ->orderByDesc('target_open_time')
                ->limit(160)
                ->get()
                ->sortBy('target_open_time')
                ->values(),
        );
    }

    private function pendingPoints(CryptoAsset $asset, string $interval): int
    {
        return $this->cache->remember(
            "stats:pending-points:{$asset->getKey()}:{$interval}",
            'forecast_stats',
            fn () => CryptoForecastPoint::query()
                ->forAsset($asset)
                ->forInterval($interval)
                ->pendingEvaluation()
                ->count(),
        );
    }

    /**
     * @param  Collection<int, CryptoForecast>  $forecasts
     * @return array{forecasts:int,evaluated_points:int,pending_points:int,mape:?float,mae:?float,direction_accuracy:?float}
     */
    private function metrics(Collection $forecasts, int $pendingPoints): array
    {
        return [
            'forecasts' => $forecasts->count(),
            'evaluated_points' => $forecasts->sum('evaluated_points'),
            'pending_points' => $pendingPoints,
            'mape' => $forecasts->whereNotNull('mean_absolute_percentage_error')->avg(
                fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_percentage_error,
            ),
            'mae' => $forecasts->whereNotNull('mean_absolute_error')->avg(
                fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_error,
            ),
            'direction_accuracy' => $forecasts->whereNotNull('direction_accuracy')->avg(
                fn (CryptoForecast $forecast): float => (float) $forecast->direction_accuracy,
            ),
        ];
    }
}
