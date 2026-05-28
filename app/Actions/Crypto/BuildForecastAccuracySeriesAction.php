<?php

namespace App\Actions\Crypto;

use Illuminate\Support\Collection;

class BuildForecastAccuracySeriesAction
{
    /**
     * @param  Collection<int, mixed>  $points
     * @return array{predicted:string,actual:string,error:string,min:float,max:float,error_max:float}
     */
    public function handle(Collection $points): array
    {
        $predicted = $points
            ->map(fn ($point): float => (float) $point->predicted_price)
            ->values();
        $actual = $points
            ->map(fn ($point): float => (float) $point->actual_close_price)
            ->values();
        $errors = $points
            ->map(fn ($point): float => (float) ($point->absolute_percentage_error ?? 0))
            ->values();

        if ($points->isEmpty()) {
            return [
                'predicted' => '',
                'actual' => '',
                'error' => '',
                'min' => 0.0,
                'max' => 0.0,
                'error_max' => 0.0,
            ];
        }

        $priceValues = $predicted->merge($actual);
        $min = (float) $priceValues->min();
        $max = (float) $priceValues->max();
        $range = max($max - $min, 0.00000001);
        $errorMax = max((float) $errors->max(), 0.00000001);
        $width = 720;
        $height = 260;
        $padding = 18;
        $usableWidth = $width - ($padding * 2);
        $usableHeight = $height - ($padding * 2);
        $totalPoints = max($points->count() - 1, 1);

        $priceScale = function (float $value, int $index) use ($min, $range, $padding, $usableWidth, $usableHeight, $totalPoints): string {
            $x = $padding + (($usableWidth / $totalPoints) * $index);
            $y = $padding + ($usableHeight - ((($value - $min) / $range) * $usableHeight));

            return round($x, 2).','.round($y, 2);
        };

        $errorScale = function (float $value, int $index) use ($errorMax, $padding, $usableWidth, $usableHeight, $totalPoints): string {
            $x = $padding + (($usableWidth / $totalPoints) * $index);
            $y = $padding + ($usableHeight - (($value / $errorMax) * $usableHeight));

            return round($x, 2).','.round($y, 2);
        };

        return [
            'predicted' => $predicted->map(fn (float $value, int $index): string => $priceScale($value, $index))->implode(' '),
            'actual' => $actual->map(fn (float $value, int $index): string => $priceScale($value, $index))->implode(' '),
            'error' => $errors->map(fn (float $value, int $index): string => $errorScale($value, $index))->implode(' '),
            'min' => $min,
            'max' => $max,
            'error_max' => (float) $errors->max(),
        ];
    }
}
