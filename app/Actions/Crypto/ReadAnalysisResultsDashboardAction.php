<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use App\Services\Crypto\CryptoCache;
use Illuminate\Support\Collection;

class ReadAnalysisResultsDashboardAction
{
    public function __construct(
        private readonly CryptoCache $cache,
    ) {}

    /**
     * @return array{
     *     assets:Collection<int,CryptoAsset>,
     *     selectedAsset:?CryptoAsset,
     *     forecasts:Collection<int,CryptoForecast>,
     *     evaluatedPoints:Collection<int,CryptoForecastPoint>,
     *     pendingPoints:Collection<int,CryptoForecastPoint>,
     *     sourceMetrics:Collection<int,array<string,mixed>>,
     *     totals:array{forecasts:int,evaluated:int,pending:int,mape:?float,direction_accuracy:?float}
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
        $evaluatedPoints = $selectedAsset ? $this->evaluatedPoints($selectedAsset, $interval) : collect();
        $pendingPoints = $selectedAsset ? $this->pendingPoints($selectedAsset, $interval) : collect();

        return [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'forecasts' => $forecasts,
            'evaluatedPoints' => $evaluatedPoints,
            'pendingPoints' => $pendingPoints,
            'sourceMetrics' => $this->sourceMetrics($forecasts, $evaluatedPoints, $pendingPoints),
            'totals' => $this->totals($forecasts, $evaluatedPoints, $pendingPoints),
        ];
    }

    /**
     * @return Collection<int, CryptoAsset>
     */
    private function assets(int $limit): Collection
    {
        return $this->cache->rememberCollection(
            "analyses:assets:{$limit}",
            'assets',
            fn () => CryptoAsset::query()->dashboardList($limit)->get(),
        );
    }

    private function selectedAsset(string $symbol): ?CryptoAsset
    {
        return $this->cache->remember(
            "analyses:selected-asset:{$symbol}",
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
            "analyses:forecasts:{$asset->getKey()}:{$interval}:60",
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
                    'target_starts_at',
                    'target_ends_at',
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
                ->limit(60)
                ->get(),
        );
    }

    /**
     * @return Collection<int, CryptoForecastPoint>
     */
    private function evaluatedPoints(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "analyses:evaluated-points:{$asset->getKey()}:{$interval}:240",
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
                ->limit(240)
                ->get(),
        );
    }

    /**
     * @return Collection<int, CryptoForecastPoint>
     */
    private function pendingPoints(CryptoAsset $asset, string $interval): Collection
    {
        return $this->cache->rememberCollection(
            "analyses:pending-points:{$asset->getKey()}:{$interval}:120",
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
                ->pendingEvaluation()
                ->orderBy('target_open_time')
                ->limit(120)
                ->get(),
        );
    }

    /**
     * @param  Collection<int, CryptoForecast>  $forecasts
     * @param  Collection<int, CryptoForecastPoint>  $evaluatedPoints
     * @param  Collection<int, CryptoForecastPoint>  $pendingPoints
     * @return Collection<int, array<string, mixed>>
     */
    private function sourceMetrics(Collection $forecasts, Collection $evaluatedPoints, Collection $pendingPoints): Collection
    {
        return collect([
            ...$forecasts->pluck('source')->all(),
            ...$evaluatedPoints->pluck('source')->all(),
            ...$pendingPoints->pluck('source')->all(),
        ])
            ->filter()
            ->unique()
            ->sort()
            ->map(function (string $source) use ($forecasts, $evaluatedPoints, $pendingPoints): array {
                $sourcePoints = $evaluatedPoints->where('source', $source);
                $directionTotal = $sourcePoints->whereNotNull('direction_correct')->count();

                return [
                    'source' => $source,
                    'forecasts' => $forecasts->where('source', $source)->count(),
                    'evaluated' => $sourcePoints->count(),
                    'pending' => $pendingPoints->where('source', $source)->count(),
                    'mape' => $sourcePoints->whereNotNull('absolute_percentage_error')->avg(
                        fn (CryptoForecastPoint $point): float => (float) $point->absolute_percentage_error,
                    ),
                    'mae' => $sourcePoints->whereNotNull('absolute_error')->avg(
                        fn (CryptoForecastPoint $point): float => (float) $point->absolute_error,
                    ),
                    'direction_accuracy' => $directionTotal === 0
                        ? null
                        : ($sourcePoints->where('direction_correct', true)->count() / $directionTotal) * 100,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, CryptoForecast>  $forecasts
     * @param  Collection<int, CryptoForecastPoint>  $evaluatedPoints
     * @param  Collection<int, CryptoForecastPoint>  $pendingPoints
     * @return array{forecasts:int,evaluated:int,pending:int,mape:?float,direction_accuracy:?float}
     */
    private function totals(Collection $forecasts, Collection $evaluatedPoints, Collection $pendingPoints): array
    {
        $directionTotal = $evaluatedPoints->whereNotNull('direction_correct')->count();

        return [
            'forecasts' => $forecasts->count(),
            'evaluated' => $evaluatedPoints->count(),
            'pending' => $pendingPoints->count(),
            'mape' => $evaluatedPoints->whereNotNull('absolute_percentage_error')->avg(
                fn (CryptoForecastPoint $point): float => (float) $point->absolute_percentage_error,
            ),
            'direction_accuracy' => $directionTotal === 0
                ? null
                : ($evaluatedPoints->where('direction_correct', true)->count() / $directionTotal) * 100,
        ];
    }
}
