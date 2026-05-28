<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use App\Services\Crypto\CryptoCache;
use App\Support\CryptoIntervals;
use Illuminate\Support\Collection;
use Throwable;

class RunForecastAnalyzersAction
{
    public function __construct(
        private readonly BuildTechnicalForecastPayloadAction $technical,
        private readonly RunTimesFmForecastAction $timesFm,
    ) {}

    /**
     * @return Collection<int, CryptoForecast>
     */
    public function handle(
        CryptoAsset $asset,
        string $interval,
        int $horizon,
        int $contextPoints,
        ?int $freshMinutes = null,
    ): Collection {
        $analyzers = $this->analyzers();
        $recentSources = $freshMinutes === null
            ? collect()
            : CryptoForecast::query()
                ->forAsset($asset)
                ->forInterval($interval)
                ->completed()
                ->whereIn('source', $analyzers)
                ->where('completed_at', '>=', now()->subMinutes($freshMinutes))
                ->get()
                ->pluck('source')
                ->unique()
                ->values();
        $technicalAnalyzers = collect($analyzers)
            ->reject(fn (string $analyzer): bool => $recentSources->contains($analyzer))
            ->reject(fn (string $analyzer): bool => $analyzer === 'timesfm')
            ->values();
        $stored = collect();

        if ($technicalAnalyzers->isNotEmpty()) {
            $stored = $stored->merge($this->runTechnicalAnalyzers($asset, $interval, $horizon, $contextPoints, $technicalAnalyzers));
        }

        if (in_array('timesfm', $analyzers, true) && ! $recentSources->contains('timesfm')) {
            try {
                $stored->push($this->timesFm->handle($asset, $interval, $horizon, $contextPoints));
            } catch (Throwable) {
                //
            }
        }

        return $stored->values();
    }

    /**
     * @param  Collection<int, string>  $analyzers
     * @return Collection<int, CryptoForecast>
     */
    private function runTechnicalAnalyzers(
        CryptoAsset $asset,
        string $interval,
        int $horizon,
        int $contextPoints,
        Collection $analyzers,
    ): Collection {
        $candles = CryptoCandle::query()
            ->forAsset($asset)
            ->forInterval($interval)
            ->latestComplete()
            ->limit($contextPoints)
            ->get()
            ->sortBy('open_time')
            ->values();

        if ($candles->isEmpty()) {
            return collect();
        }

        $values = $candles
            ->pluck('close_price')
            ->map(fn (string $value): float => (float) $value)
            ->values()
            ->all();
        $firstCandle = $candles->first();
        $lastCandle = $candles->last();
        $basePrice = (float) $lastCandle->close_price;
        $targetStartsAt = CryptoIntervals::addSteps($lastCandle->open_time, $interval, 1);
        $targetEndsAt = CryptoIntervals::addSteps($lastCandle->open_time, $interval, $horizon);

        return $analyzers
            ->map(function (string $analyzer) use ($asset, $interval, $horizon, $values, $firstCandle, $lastCandle, $basePrice, $targetStartsAt, $targetEndsAt): CryptoForecast {
                $payload = $this->technical->handle($values, $horizon, $analyzer);
                $pointForecast = $payload['point_forecast'];
                $quantileForecast = $payload['quantile_forecast'];

                $forecast = CryptoForecast::query()->create([
                    'crypto_asset_id' => $asset->getKey(),
                    'source' => $analyzer,
                    'interval' => $interval,
                    'context_points' => count($values),
                    'horizon' => $horizon,
                    'status' => 'completed',
                    'started_at' => now(),
                    'completed_at' => now(),
                    'input_starts_at' => $firstCandle->open_time,
                    'input_ends_at' => $lastCandle->open_time,
                    'target_starts_at' => $targetStartsAt,
                    'target_ends_at' => $targetEndsAt,
                    'base_price' => (string) $basePrice,
                    'total_points' => count($pointForecast),
                    'point_forecast' => $pointForecast,
                    'quantile_forecast' => $quantileForecast,
                    'config' => ['engine' => $payload['engine'], 'basis' => 'stored-candles'],
                ]);

                $this->storeForecastPoints($forecast, $asset, $basePrice, $pointForecast, $quantileForecast);

                return $forecast->refresh();
            })
            ->values();
    }

    /**
     * @param  array<int, float>  $pointForecast
     * @param  array<int, array<int, float>>  $quantileForecast
     */
    private function storeForecastPoints(
        CryptoForecast $forecast,
        CryptoAsset $asset,
        float $basePrice,
        array $pointForecast,
        array $quantileForecast,
    ): void {
        $now = now();
        $rows = [];

        foreach ($pointForecast as $index => $predictedPrice) {
            $step = $index + 1;
            $quantiles = $quantileForecast[$index] ?? [];

            $rows[] = [
                'crypto_forecast_id' => $forecast->getKey(),
                'crypto_asset_id' => $asset->getKey(),
                'source' => $forecast->source,
                'interval' => $forecast->interval,
                'step' => $step,
                'target_open_time' => CryptoIntervals::addSteps($forecast->input_ends_at, $forecast->interval, $step),
                'base_price' => (string) $basePrice,
                'predicted_price' => (string) $predictedPrice,
                'quantile_low' => isset($quantiles[0]) ? (string) $quantiles[0] : null,
                'quantile_median' => isset($quantiles[1]) ? (string) $quantiles[1] : null,
                'quantile_high' => isset($quantiles[2]) ? (string) $quantiles[2] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        CryptoForecastPoint::query()->upsert(
            $rows,
            ['crypto_forecast_id', 'step'],
            [
                'crypto_asset_id',
                'source',
                'interval',
                'target_open_time',
                'base_price',
                'predicted_price',
                'quantile_low',
                'quantile_median',
                'quantile_high',
                'updated_at',
            ],
        );

        app(CryptoCache::class)->flush();
    }

    /**
     * @return array<int, string>
     */
    private function analyzers(): array
    {
        $configured = collect(config('crypto.forecasting.analyzers', []))
            ->map(fn (mixed $analyzer): string => strtolower(trim((string) $analyzer)))
            ->filter(fn (string $analyzer): bool => $analyzer !== '')
            ->unique()
            ->values();

        if ((bool) config('crypto.forecasting.timesfm.enabled', false)) {
            $configured->push('timesfm');
        }

        return $configured
            ->unique()
            ->values()
            ->all();
    }
}
