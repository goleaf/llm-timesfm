<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use App\Services\Crypto\CryptoCache;
use App\Support\CryptoIntervals;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class RunTimesFmForecastAction
{
    public function handle(CryptoAsset $asset, string $interval, int $horizon, int $contextPoints): CryptoForecast
    {
        $candles = CryptoCandle::query()
            ->forAsset($asset)
            ->forInterval($interval)
            ->latestComplete()
            ->limit($contextPoints)
            ->get()
            ->sortBy('open_time')
            ->values();

        if ($candles->isEmpty()) {
            throw new RuntimeException("No candle history is available for {$asset->symbol} {$interval}.");
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

        $forecast = CryptoForecast::query()->create([
            'crypto_asset_id' => $asset->getKey(),
            'source' => config('crypto.forecasting.timesfm.enabled') ? 'timesfm' : 'baseline',
            'interval' => $interval,
            'context_points' => count($values),
            'horizon' => $horizon,
            'status' => 'running',
            'started_at' => now(),
            'input_starts_at' => $firstCandle->open_time,
            'input_ends_at' => $lastCandle->open_time,
            'target_starts_at' => $targetStartsAt,
            'target_ends_at' => $targetEndsAt,
            'base_price' => (string) $basePrice,
            'total_points' => $horizon,
            'config' => [
                'model_id' => config('crypto.forecasting.timesfm.model_id'),
                'max_context' => config('crypto.forecasting.timesfm.max_context'),
                'max_horizon' => config('crypto.forecasting.timesfm.max_horizon'),
            ],
        ]);

        try {
            $payload = config('crypto.forecasting.timesfm.enabled')
                ? $this->runTimesFmProcess($values, $horizon)
                : $this->baselineForecast($values, $horizon);
            $pointForecast = array_map(
                fn (float|int|string $value): float => (float) $value,
                $payload['point_forecast'],
            );
            $quantileForecast = isset($payload['quantile_forecast'])
                ? array_map(
                    fn (array $row): array => array_map(
                        fn (float|int|string $value): float => (float) $value,
                        $row,
                    ),
                    $payload['quantile_forecast'],
                )
                : null;

            $forecast->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_points' => count($pointForecast),
                'point_forecast' => $pointForecast,
                'quantile_forecast' => $quantileForecast,
                'config' => array_merge($forecast->config ?? [], [
                    'engine' => $payload['engine'] ?? $forecast->source,
                ]),
            ]);

            $this->storeForecastPoints($forecast->refresh(), $asset, $basePrice, $pointForecast, $quantileForecast);
        } catch (RuntimeException $exception) {
            $forecast->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $forecast->refresh();
    }

    /**
     * @param  array<int, float>  $pointForecast
     * @param  array<int, array<int, float>>|null  $quantileForecast
     */
    private function storeForecastPoints(
        CryptoForecast $forecast,
        CryptoAsset $asset,
        float $basePrice,
        array $pointForecast,
        ?array $quantileForecast,
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
     * @param  array<int, float>  $values
     * @return array{point_forecast:array<int,float>,quantile_forecast?:array<int,array<int,float>>,engine?:string}
     */
    private function runTimesFmProcess(array $values, int $horizon): array
    {
        $input = json_encode([
            'values' => $values,
            'horizon' => $horizon,
            'model_id' => config('crypto.forecasting.timesfm.model_id'),
            'max_context' => config('crypto.forecasting.timesfm.max_context'),
            'max_horizon' => config('crypto.forecasting.timesfm.max_horizon'),
        ], JSON_THROW_ON_ERROR);

        $result = Process::path(base_path())
            ->timeout((int) config('crypto.forecasting.timesfm.timeout'))
            ->input($input)
            ->run([
                (string) config('crypto.forecasting.timesfm.python'),
                (string) config('crypto.forecasting.timesfm.script'),
            ]);

        if ($result->failed()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'TimesFM process failed.');
        }

        $decoded = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || ! isset($decoded['point_forecast']) || ! is_array($decoded['point_forecast'])) {
            throw new RuntimeException('TimesFM process returned an invalid payload.');
        }

        return $decoded;
    }

    /**
     * @param  array<int, float>  $values
     * @return array{point_forecast:array<int,float>,quantile_forecast:array<int,array<int,float>>,engine:string}
     */
    private function baselineForecast(array $values, int $horizon): array
    {
        $lastValue = (float) end($values);
        $forecast = array_fill(0, $horizon, $lastValue);

        return [
            'point_forecast' => $forecast,
            'quantile_forecast' => array_map(
                fn (float $value): array => [$value * 0.99, $value, $value * 1.01],
                $forecast,
            ),
            'engine' => 'baseline-last-value',
        ];
    }
}
