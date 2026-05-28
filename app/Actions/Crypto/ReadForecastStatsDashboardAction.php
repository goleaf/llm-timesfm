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
     *     pendingPointRows:Collection<int,CryptoForecastPoint>,
     *     pendingPoints:int,
     *     metrics:array<string,mixed>,
     *     engineRows:Collection<int,array<string,mixed>>,
     *     bestPoints:Collection<int,CryptoForecastPoint>,
     *     worstPoints:Collection<int,CryptoForecastPoint>
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
        $pendingPointRows = $selectedAsset ? $this->pendingPointRows($selectedAsset, $interval) : collect();
        $pendingPoints = $selectedAsset ? $this->pendingPoints($selectedAsset, $interval) : 0;

        return [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'forecasts' => $forecasts,
            'points' => $points,
            'pendingPointRows' => $pendingPointRows,
            'pendingPoints' => $pendingPoints,
            'metrics' => $this->metrics($forecasts, $pendingPoints),
            'engineRows' => $this->engineRows($forecasts, $points, $pendingPointRows),
            'bestPoints' => $this->bestPoints($points),
            'worstPoints' => $this->worstPoints($points),
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
            "stats:forecasts:{$asset->getKey()}:{$interval}:50",
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
                    'started_at',
                    'completed_at',
                    'input_starts_at',
                    'input_ends_at',
                    'target_starts_at',
                    'target_ends_at',
                    'base_price',
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
                ->limit(50)
                ->get(),
        );
    }

    /**
     * @return Collection<int, CryptoForecastPoint>
     */
    private function points(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "stats:points:{$asset->getKey()}:{$interval}:500",
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
                    'quantile_low',
                    'quantile_median',
                    'quantile_high',
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
                ->limit(500)
                ->get()
                ->sortBy('target_open_time')
                ->values(),
        );
    }

    /**
     * @return Collection<int, CryptoForecastPoint>
     */
    private function pendingPointRows(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "stats:pending-point-rows:{$asset->getKey()}:{$interval}:500",
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
                    'quantile_low',
                    'quantile_median',
                    'quantile_high',
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
                ->pendingEvaluation()
                ->orderBy('target_open_time')
                ->limit(500)
                ->get(),
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
     * @return array<string,mixed>
     */
    private function metrics(Collection $forecasts, int $pendingPoints): array
    {
        $evaluatedForecasts = $forecasts->whereNotNull('evaluated_at');

        return [
            'forecasts' => $forecasts->count(),
            'evaluated_points' => $forecasts->sum('evaluated_points'),
            'pending_points' => $pendingPoints,
            'source_count' => $forecasts->pluck('source')->unique()->count(),
            'total_points' => $forecasts->sum('total_points'),
            'coverage' => $forecasts->sum('total_points') > 0
                ? ($forecasts->sum('evaluated_points') / $forecasts->sum('total_points')) * 100
                : null,
            'mape' => $forecasts->whereNotNull('mean_absolute_percentage_error')->avg(
                fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_percentage_error,
            ),
            'mae' => $forecasts->whereNotNull('mean_absolute_error')->avg(
                fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_error,
            ),
            'best_mape' => $forecasts->whereNotNull('mean_absolute_percentage_error')->min(
                fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_percentage_error,
            ),
            'worst_mape' => $forecasts->whereNotNull('mean_absolute_percentage_error')->max(
                fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_percentage_error,
            ),
            'direction_accuracy' => $forecasts->whereNotNull('direction_accuracy')->avg(
                fn (CryptoForecast $forecast): float => (float) $forecast->direction_accuracy,
            ),
            'last_evaluated_at' => $evaluatedForecasts->max('evaluated_at'),
            'latest_completed_at' => $forecasts->max('completed_at'),
        ];
    }

    /**
     * @param  Collection<int, CryptoForecast>  $forecasts
     * @param  Collection<int, CryptoForecastPoint>  $points
     * @param  Collection<int, CryptoForecastPoint>  $pendingPoints
     * @return Collection<int, array<string,mixed>>
     */
    private function engineRows(Collection $forecasts, Collection $points, Collection $pendingPoints): Collection
    {
        return $forecasts
            ->pluck('source')
            ->merge($points->pluck('source'))
            ->merge($pendingPoints->pluck('source'))
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $source) use ($forecasts, $points, $pendingPoints): array {
                $sourceForecasts = $forecasts->where('source', $source);
                $sourcePoints = $points->where('source', $source);
                $sourcePendingPoints = $pendingPoints->where('source', $source);
                $correctPoints = $sourcePoints->where('direction_correct', true)->count();

                return [
                    'source' => $source,
                    'runs' => $sourceForecasts->count(),
                    'evaluated_points' => $sourcePoints->count(),
                    'pending_points' => $sourcePendingPoints->count(),
                    'mape' => $sourcePoints->whereNotNull('absolute_percentage_error')->avg(
                        fn (CryptoForecastPoint $point): float => (float) $point->absolute_percentage_error,
                    ),
                    'mae' => $sourcePoints->whereNotNull('absolute_error')->avg(
                        fn (CryptoForecastPoint $point): float => (float) $point->absolute_error,
                    ),
                    'direction_accuracy' => $sourcePoints->isNotEmpty()
                        ? ($correctPoints / $sourcePoints->count()) * 100
                        : null,
                    'latest_completed_at' => $sourceForecasts->max('completed_at'),
                    'next_pending_at' => $sourcePendingPoints->min('target_open_time'),
                ];
            });
    }

    /**
     * @param  Collection<int, CryptoForecastPoint>  $points
     * @return Collection<int, CryptoForecastPoint>
     */
    private function bestPoints(Collection $points): Collection
    {
        return $points
            ->whereNotNull('absolute_percentage_error')
            ->sortBy(fn (CryptoForecastPoint $point): float => (float) $point->absolute_percentage_error)
            ->take(12)
            ->values();
    }

    /**
     * @param  Collection<int, CryptoForecastPoint>  $points
     * @return Collection<int, CryptoForecastPoint>
     */
    private function worstPoints(Collection $points): Collection
    {
        return $points
            ->whereNotNull('absolute_percentage_error')
            ->sortByDesc(fn (CryptoForecastPoint $point): float => (float) $point->absolute_percentage_error)
            ->take(12)
            ->values();
    }
}
