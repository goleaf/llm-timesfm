<?php

namespace App\Actions\Crypto;

use App\Models\CryptoForecast;
use Illuminate\Support\Collection;

class BuildMarketSeriesAction
{
    /**
     * @param  Collection<int, mixed>  $candles
     * @return array{history:string,forecast:string,min:float,max:float,latest:?float}
     */
    public function handle(Collection $candles, ?CryptoForecast $forecast = null): array
    {
        $historyValues = $candles
            ->map(fn ($candle): float => (float) $candle->close_price)
            ->values();

        $forecastValues = collect($forecast?->point_forecast ?? [])
            ->map(fn (float|int|string $value): float => (float) $value)
            ->values();

        $allValues = $historyValues->merge($forecastValues);

        if ($allValues->isEmpty()) {
            return [
                'history' => '',
                'forecast' => '',
                'min' => 0.0,
                'max' => 0.0,
                'latest' => null,
            ];
        }

        $min = (float) $allValues->min();
        $max = (float) $allValues->max();
        $range = max($max - $min, 0.00000001);
        $width = 720;
        $height = 260;
        $padding = 18;
        $usableWidth = $width - ($padding * 2);
        $usableHeight = $height - ($padding * 2);
        $totalPoints = max($historyValues->count() + $forecastValues->count() - 1, 1);

        $scale = function (float $value, int $index) use ($min, $range, $padding, $usableWidth, $usableHeight, $totalPoints): string {
            $x = $padding + (($usableWidth / $totalPoints) * $index);
            $y = $padding + ($usableHeight - ((($value - $min) / $range) * $usableHeight));

            return round($x, 2).','.round($y, 2);
        };

        $historyPoints = $historyValues
            ->map(fn (float $value, int $index): string => $scale($value, $index))
            ->implode(' ');

        $forecastOffset = max($historyValues->count() - 1, 0);
        $forecastPoints = $forecastValues
            ->map(fn (float $value, int $index): string => $scale($value, $forecastOffset + $index + 1))
            ->implode(' ');

        return [
            'history' => $historyPoints,
            'forecast' => $forecastPoints,
            'min' => $min,
            'max' => $max,
            'latest' => $historyValues->last(),
        ];
    }
}
