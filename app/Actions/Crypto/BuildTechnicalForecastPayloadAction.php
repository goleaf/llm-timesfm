<?php

namespace App\Actions\Crypto;

use InvalidArgumentException;

class BuildTechnicalForecastPayloadAction
{
    /**
     * @param  array<int, float>  $values
     * @return array{point_forecast:array<int,float>,quantile_forecast:array<int,array<int,float>>,engine:string}
     */
    public function handle(array $values, int $horizon, string $analyzer): array
    {
        if ($values === []) {
            throw new InvalidArgumentException('Technical analysis needs candle close values.');
        }

        $forecast = match ($analyzer) {
            'trend' => $this->trend($values, $horizon),
            'moving-average' => $this->movingAverage($values, $horizon),
            'ema' => $this->ema($values, $horizon),
            'momentum' => $this->momentum($values, $horizon),
            'baseline' => $this->baseline($values, $horizon),
            default => throw new InvalidArgumentException("Unsupported forecast analyzer [{$analyzer}]."),
        };

        return [
            'point_forecast' => $forecast,
            'quantile_forecast' => $this->quantiles($values, $forecast),
            'engine' => $analyzer,
        ];
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function trend(array $values, int $horizon): array
    {
        $count = count($values);
        $xMean = ($count - 1) / 2;
        $yMean = array_sum($values) / $count;
        $numerator = 0.0;
        $denominator = 0.0;

        foreach ($values as $index => $value) {
            $xDelta = $index - $xMean;
            $numerator += $xDelta * ($value - $yMean);
            $denominator += $xDelta ** 2;
        }

        $slope = $denominator === 0.0 ? 0.0 : $numerator / $denominator;
        $intercept = $yMean - ($slope * $xMean);

        return collect(range($count, $count + $horizon - 1))
            ->map(fn (int $index): float => $this->positive($intercept + ($slope * $index)))
            ->all();
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function movingAverage(array $values, int $horizon): array
    {
        $window = min(max((int) floor(count($values) / 8), 3), 24);
        $series = $values;
        $forecast = [];

        for ($step = 0; $step < $horizon; $step++) {
            $average = array_sum(array_slice($series, -$window)) / min(count($series), $window);
            $forecast[] = $this->positive($average);
            $series[] = $average;
        }

        return $forecast;
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function ema(array $values, int $horizon): array
    {
        $window = min(max((int) floor(count($values) / 10), 4), 30);
        $alpha = 2 / ($window + 1);
        $ema = (float) $values[0];

        foreach ($values as $value) {
            $ema = ($value * $alpha) + ($ema * (1 - $alpha));
        }

        $recent = array_slice($values, -$window);
        $drift = ($recent[array_key_last($recent)] - $recent[0]) / max(count($recent) - 1, 1);

        return collect(range(1, $horizon))
            ->map(fn (int $step): float => $this->positive($ema + ($drift * $step * 0.35)))
            ->all();
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function momentum(array $values, int $horizon): array
    {
        $window = min(max((int) floor(count($values) / 12), 3), 16);
        $recent = array_slice($values, -($window + 1));
        $deltas = [];

        for ($index = 1; $index < count($recent); $index++) {
            $deltas[] = $recent[$index] - $recent[$index - 1];
        }

        $averageDelta = $deltas === [] ? 0.0 : array_sum($deltas) / count($deltas);
        $last = (float) end($values);

        return collect(range(1, $horizon))
            ->map(fn (int $step): float => $this->positive($last + ($averageDelta * $step * (0.92 ** $step))))
            ->all();
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    private function baseline(array $values, int $horizon): array
    {
        return array_fill(0, $horizon, (float) end($values));
    }

    /**
     * @param  array<int, float>  $values
     * @param  array<int, float>  $forecast
     * @return array<int, array<int, float>>
     */
    private function quantiles(array $values, array $forecast): array
    {
        $recent = array_slice($values, -min(count($values), 30));
        $deltas = [];

        for ($index = 1; $index < count($recent); $index++) {
            $deltas[] = abs($recent[$index] - $recent[$index - 1]);
        }

        $averageMove = $deltas === [] ? max(abs((float) end($values)) * 0.001, 0.00000001) : array_sum($deltas) / count($deltas);

        return collect($forecast)
            ->map(fn (float $value, int $index): array => [
                $this->positive($value - ($averageMove * sqrt($index + 1) * 1.5)),
                $this->positive($value),
                $this->positive($value + ($averageMove * sqrt($index + 1) * 1.5)),
            ])
            ->all();
    }

    private function positive(float $value): float
    {
        return max($value, 0.00000001);
    }
}
