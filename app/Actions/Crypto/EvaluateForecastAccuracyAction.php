<?php

namespace App\Actions\Crypto;

use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use App\Services\Crypto\CryptoCache;

class EvaluateForecastAccuracyAction
{
    /**
     * @return array{points:int,forecasts:int}
     */
    public function handle(int $limit = 1000): array
    {
        $points = CryptoForecastPoint::query()
            ->dueForEvaluation()
            ->orderBy('target_open_time')
            ->limit($limit)
            ->get();

        if ($points->isEmpty()) {
            return ['points' => 0, 'forecasts' => 0];
        }

        $candles = CryptoCandle::query()
            ->select(['id', 'crypto_asset_id', 'interval', 'open_time', 'close_price'])
            ->whereIn('crypto_asset_id', $points->pluck('crypto_asset_id')->unique()->values())
            ->whereIn('interval', $points->pluck('interval')->unique()->values())
            ->where('open_time', '>=', $points->min('target_open_time'))
            ->where('open_time', '<=', $points->max('target_open_time'))
            ->get()
            ->keyBy(fn (CryptoCandle $candle): string => $this->pointKey(
                (int) $candle->crypto_asset_id,
                (string) $candle->interval,
                $candle->open_time->getTimestamp(),
            ));

        $now = now();
        $pointRows = [];

        foreach ($points as $point) {
            $candle = $candles->get($this->pointKey(
                (int) $point->crypto_asset_id,
                (string) $point->interval,
                $point->target_open_time->getTimestamp(),
            ));

            if (! $candle) {
                continue;
            }

            $actual = (float) $candle->close_price;
            $predicted = (float) $point->predicted_price;
            $base = (float) ($point->base_price ?? $predicted);
            $absoluteError = abs($actual - $predicted);
            $percentageError = $actual == 0.0 ? null : ($absoluteError / abs($actual)) * 100;

            $pointRows[] = [
                'id' => $point->getKey(),
                'crypto_forecast_id' => $point->crypto_forecast_id,
                'crypto_asset_id' => $point->crypto_asset_id,
                'source' => $point->source,
                'interval' => $point->interval,
                'step' => $point->step,
                'target_open_time' => $point->target_open_time,
                'base_price' => $point->base_price,
                'predicted_price' => $point->predicted_price,
                'quantile_low' => $point->quantile_low,
                'quantile_median' => $point->quantile_median,
                'quantile_high' => $point->quantile_high,
                'actual_close_price' => (string) $actual,
                'absolute_error' => (string) $absoluteError,
                'absolute_percentage_error' => $percentageError === null ? null : (string) $percentageError,
                'direction_correct' => $this->direction($predicted, $base) === $this->direction($actual, $base),
                'evaluated_at' => $now,
                'created_at' => $point->created_at ?? $now,
                'updated_at' => $now,
            ];
        }

        if ($pointRows === []) {
            return ['points' => 0, 'forecasts' => 0];
        }

        CryptoForecastPoint::query()->upsert(
            $pointRows,
            ['id'],
            [
                'actual_close_price',
                'absolute_error',
                'absolute_percentage_error',
                'direction_correct',
                'evaluated_at',
                'updated_at',
            ],
        );

        $forecastIds = collect($pointRows)->pluck('crypto_forecast_id')->unique()->values();
        $forecasts = CryptoForecast::query()
            ->select([
                'id',
                'crypto_asset_id',
                'source',
                'interval',
                'context_points',
                'horizon',
                'status',
                'created_at',
            ])
            ->whereIn('id', $forecastIds)
            ->get()
            ->keyBy('id');
        $evaluatedPoints = CryptoForecastPoint::query()
            ->select([
                'id',
                'crypto_forecast_id',
                'absolute_error',
                'absolute_percentage_error',
                'direction_correct',
                'evaluated_at',
            ])
            ->whereIn('crypto_forecast_id', $forecastIds)
            ->whereNotNull('evaluated_at')
            ->get()
            ->groupBy('crypto_forecast_id');

        $forecastRows = $evaluatedPoints
            ->map(function ($forecastPoints, int $forecastId) use ($now, $forecasts): array {
                $directionTotal = $forecastPoints->whereNotNull('direction_correct')->count();
                $forecast = $forecasts->get($forecastId);

                return [
                    'id' => $forecastId,
                    'crypto_asset_id' => $forecast->crypto_asset_id,
                    'source' => $forecast->source,
                    'interval' => $forecast->interval,
                    'context_points' => $forecast->context_points,
                    'horizon' => $forecast->horizon,
                    'status' => $forecast->status,
                    'evaluated_points' => $forecastPoints->count(),
                    'mean_absolute_error' => (string) $forecastPoints->avg(fn (CryptoForecastPoint $point): float => (float) $point->absolute_error),
                    'mean_absolute_percentage_error' => (string) $forecastPoints
                        ->whereNotNull('absolute_percentage_error')
                        ->avg(fn (CryptoForecastPoint $point): float => (float) $point->absolute_percentage_error),
                    'direction_accuracy' => $directionTotal === 0
                        ? null
                        : (string) (($forecastPoints->where('direction_correct', true)->count() / $directionTotal) * 100),
                    'evaluated_at' => $now,
                    'created_at' => $forecast->created_at ?? $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        CryptoForecast::query()->upsert(
            $forecastRows,
            ['id'],
            [
                'evaluated_points',
                'mean_absolute_error',
                'mean_absolute_percentage_error',
                'direction_accuracy',
                'evaluated_at',
                'updated_at',
            ],
        );

        app(CryptoCache::class)->flush();

        return [
            'points' => count($pointRows),
            'forecasts' => count($forecastRows),
        ];
    }

    private function pointKey(int $assetId, string $interval, int $timestamp): string
    {
        return "{$assetId}:{$interval}:{$timestamp}";
    }

    private function direction(float $value, float $base): int
    {
        return $value <=> $base;
    }
}
