<?php

namespace App\Actions\Crypto;

use Illuminate\Support\Collection;

class BuildForecastAccuracySeriesAction
{
    /**
     * @param  Collection<int, mixed>  $points
     * @return array{predicted:string,actual:string,error:string,min:float,max:float,error_max:float,tooltip:array<string,mixed>,error_tooltip:array<string,mixed>}
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
                'tooltip' => $this->payload([], [], __('ui.chart.forecast_accuracy')),
                'error_tooltip' => $this->payload([], [], __('ui.chart.forecast_error')),
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

        $priceScale = function (float $value, int $index) use ($min, $range, $padding, $usableWidth, $usableHeight, $totalPoints): array {
            $x = $padding + (($usableWidth / $totalPoints) * $index);
            $y = $padding + ($usableHeight - ((($value - $min) / $range) * $usableHeight));

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        };

        $errorScale = function (float $value, int $index) use ($errorMax, $padding, $usableWidth, $usableHeight, $totalPoints): array {
            $x = $padding + (($usableWidth / $totalPoints) * $index);
            $y = $padding + ($usableHeight - (($value / $errorMax) * $usableHeight));

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        };

        $predictedPoints = $points
            ->map(function ($point, int $index) use ($priceScale): array {
                $position = $priceScale((float) $point->predicted_price, $index);

                return [
                    ...$position,
                    'title' => __('ui.chart.predicted'),
                    'value' => $this->price($point->predicted_price),
                    'time' => $point->target_open_time->format('Y-m-d H:i:s'),
                    'rows' => $this->forecastRows($point, __('ui.chart.predicted'), $point->predicted_price),
                ];
            })
            ->values();
        $actualPoints = $points
            ->map(function ($point, int $index) use ($priceScale): array {
                $position = $priceScale((float) $point->actual_close_price, $index);

                return [
                    ...$position,
                    'title' => __('ui.chart.actual'),
                    'value' => $this->price($point->actual_close_price),
                    'time' => $point->target_open_time->format('Y-m-d H:i:s'),
                    'rows' => $this->forecastRows($point, __('ui.chart.actual'), $point->actual_close_price),
                ];
            })
            ->values();
        $errorPoints = $points
            ->map(function ($point, int $index) use ($errorScale): array {
                $position = $errorScale((float) ($point->absolute_percentage_error ?? 0), $index);

                return [
                    ...$position,
                    'title' => __('ui.common.error'),
                    'value' => $this->percent($point->absolute_percentage_error),
                    'time' => $point->target_open_time->format('Y-m-d H:i:s'),
                    'rows' => [
                        ['label' => __('ui.chart.type'), 'value' => __('ui.chart.forecast_error')],
                        ['label' => __('ui.chart.target_time'), 'value' => $point->target_open_time->format('Y-m-d H:i:s')],
                        ['label' => __('ui.chart.predicted'), 'value' => $this->price($point->predicted_price)],
                        ['label' => __('ui.chart.actual'), 'value' => $this->price($point->actual_close_price)],
                        ['label' => __('ui.chart.absolute_error'), 'value' => $this->price($point->absolute_error)],
                        ['label' => __('ui.chart.percent_error'), 'value' => $this->percent($point->absolute_percentage_error)],
                        ['label' => __('ui.common.direction'), 'value' => $point->direction_correct ? __('ui.common.correct') : __('ui.common.wrong')],
                        ['label' => __('ui.chart.forecast_run'), 'value' => '#'.$point->crypto_forecast_id],
                    ],
                ];
            })
            ->values();

        return [
            'predicted' => $this->polyline($predictedPoints),
            'actual' => $this->polyline($actualPoints),
            'error' => $this->polyline($errorPoints),
            'min' => $min,
            'max' => $max,
            'error_max' => (float) $errors->max(),
            'tooltip' => $this->payload($predictedPoints->all(), $actualPoints->all(), __('ui.chart.forecast_accuracy')),
            'error_tooltip' => $this->payload($errorPoints->all(), [], __('ui.chart.forecast_error')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $primaryPoints
     * @param  array<int, array<string, mixed>>  $secondaryPoints
     * @return array<string, mixed>
     */
    private function payload(array $primaryPoints, array $secondaryPoints, string $title): array
    {
        return [
            'title' => $title,
            'point_count' => count($primaryPoints) + count($secondaryPoints),
            'series' => [
                [
                    'label' => $title === __('ui.chart.forecast_error') ? __('ui.common.error') : __('ui.chart.predicted'),
                    'color' => $title === __('ui.chart.forecast_error') ? '#fb7185' : '#fbbf24',
                    'points' => $primaryPoints,
                ],
                [
                    'label' => __('ui.chart.actual'),
                    'color' => '#22d3ee',
                    'points' => $secondaryPoints,
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, array{x:float|int,y:float|int}>  $points
     */
    private function polyline(Collection $points): string
    {
        return $points
            ->map(fn (array $point): string => $point['x'].','.$point['y'])
            ->implode(' ');
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    private function forecastRows(mixed $point, string $type, float|int|string|null $value): array
    {
        return [
            ['label' => __('ui.chart.type'), 'value' => $type],
            ['label' => __('ui.chart.target_time'), 'value' => $point->target_open_time->format('Y-m-d H:i:s')],
            ['label' => __('ui.chart.value'), 'value' => $this->price($value)],
            ['label' => __('ui.chart.predicted'), 'value' => $this->price($point->predicted_price)],
            ['label' => __('ui.chart.actual'), 'value' => $this->price($point->actual_close_price)],
            ['label' => __('ui.chart.percent_error'), 'value' => $this->percent($point->absolute_percentage_error)],
            ['label' => __('ui.common.direction'), 'value' => $point->direction_correct ? __('ui.common.correct') : __('ui.common.wrong')],
            ['label' => __('ui.chart.forecast_run'), 'value' => '#'.$point->crypto_forecast_id],
        ];
    }

    private function price(float|int|string|null $value): string
    {
        if ($value === null) {
            return __('ui.common.na');
        }

        $number = (float) $value;

        return number_format($number, abs($number) >= 1 ? 2 : 8);
    }

    private function percent(float|int|string|null $value): string
    {
        return $value === null ? __('ui.common.na') : number_format((float) $value, 2).'%';
    }
}
