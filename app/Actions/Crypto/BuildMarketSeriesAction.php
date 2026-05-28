<?php

namespace App\Actions\Crypto;

use App\Models\CryptoForecast;
use App\Models\CryptoPriceSnapshot;
use App\Support\CryptoIntervals;
use Illuminate\Support\Collection;

class BuildMarketSeriesAction
{
    /**
     * @param  Collection<int, mixed>  $candles
     * @param  Collection<int, CryptoForecast>|CryptoForecast|null  $forecasts
     * @return array{history:string,forecast:string,forecast_series:array<int,array<string,mixed>>,min:float,max:float,latest:?float,scale_ticks:array<int,array<string,mixed>>,summary_cards:array<int,array<string,string>>,latest_marker:?array<string,mixed>,forecast_labels:array<int,array<string,mixed>>,point_ledger:array<int,array<string,string>>,tooltip:array<string,mixed>}
     */
    public function handle(Collection $candles, Collection|CryptoForecast|null $forecasts = null, ?CryptoPriceSnapshot $latestSnapshot = null): array
    {
        $forecastRuns = $forecasts instanceof CryptoForecast
            ? collect([$forecasts])
            : ($forecasts ?? collect());
        $historySource = $candles
            ->map(fn ($candle): array => [
                'kind' => 'candle',
                'time' => $candle->open_time,
                'value' => (float) $candle->close_price,
                'rows' => [
                    ['label' => 'Type', 'value' => 'Candle close'],
                    ['label' => 'Time', 'value' => $candle->open_time->format('Y-m-d H:i:s')],
                    ['label' => 'Open', 'value' => $this->price($candle->open_price)],
                    ['label' => 'High', 'value' => $this->price($candle->high_price)],
                    ['label' => 'Low', 'value' => $this->price($candle->low_price)],
                    ['label' => 'Close', 'value' => $this->price($candle->close_price)],
                    ['label' => 'Base volume', 'value' => $this->number($candle->base_volume)],
                    ['label' => 'Quote volume', 'value' => $this->number($candle->quote_volume)],
                    ['label' => 'Trades', 'value' => number_format((int) $candle->trade_count)],
                ],
            ])
            ->values();

        $lastCandle = $candles->last();

        if ($latestSnapshot && (! $lastCandle || $latestSnapshot->source_event_at->greaterThan($lastCandle->open_time))) {
            $historySource->push([
                'kind' => 'live',
                'time' => $latestSnapshot->source_event_at,
                'value' => (float) $latestSnapshot->price,
                'rows' => [
                    ['label' => 'Type', 'value' => 'Live price'],
                    ['label' => 'Time', 'value' => $latestSnapshot->source_event_at->format('Y-m-d H:i:s')],
                    ['label' => 'Last', 'value' => $this->price($latestSnapshot->price)],
                    ['label' => 'Bid', 'value' => $this->price($latestSnapshot->bid_price)],
                    ['label' => 'Ask', 'value' => $this->price($latestSnapshot->ask_price)],
                    ['label' => '24h high', 'value' => $this->price($latestSnapshot->high_price)],
                    ['label' => '24h low', 'value' => $this->price($latestSnapshot->low_price)],
                    ['label' => '24h change', 'value' => $this->percent($latestSnapshot->price_change_percent)],
                    ['label' => 'Quote volume', 'value' => $this->number($latestSnapshot->quote_volume)],
                    ['label' => 'Trades', 'value' => number_format((int) $latestSnapshot->trade_count)],
                ],
            ]);
        }

        $historyValues = $historySource
            ->pluck('value')
            ->values();

        $forecastValueGroups = $forecastRuns
            ->map(fn (CryptoForecast $forecast): Collection => collect($forecast->point_forecast ?? [])
                ->map(fn (float|int|string $value): float => (float) $value)
                ->values())
            ->values();
        $forecastValues = $forecastValueGroups
            ->flatten()
            ->values();

        $allValues = $historyValues->merge($forecastValues);

        if ($allValues->isEmpty()) {
            return [
                'history' => '',
                'forecast' => '',
                'forecast_series' => [],
                'min' => 0.0,
                'max' => 0.0,
                'latest' => null,
                'scale_ticks' => [],
                'summary_cards' => [],
                'latest_marker' => null,
                'forecast_labels' => [],
                'point_ledger' => [],
                'tooltip' => $this->payload([], []),
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
        $maxForecastPoints = (int) $forecastValueGroups
            ->map(fn (Collection $values): int => $values->count())
            ->max();
        $totalPoints = max($historyValues->count() + $maxForecastPoints - 1, 1);

        $scale = function (float $value, int $index) use ($min, $range, $padding, $usableWidth, $usableHeight, $totalPoints): array {
            $x = $padding + (($usableWidth / $totalPoints) * $index);
            $y = $padding + ($usableHeight - ((($value - $min) / $range) * $usableHeight));

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        };

        $historyMeta = $historySource
            ->map(function (array $point, int $index) use ($scale): array {
                $position = $scale((float) $point['value'], $index);

                return [
                    ...$position,
                    'title' => $point['kind'] === 'live' ? 'Live price' : 'Candle',
                    'value' => $this->price($point['value']),
                    'time' => $point['time']->format('Y-m-d H:i:s'),
                    'rows' => $point['rows'],
                ];
            })
            ->values();

        $historyPoints = $this->polyline($historyMeta);

        $forecastOffset = max($historyValues->count() - 1, 0);
        $forecastSeries = $forecastRuns
            ->values()
            ->map(function (CryptoForecast $forecast, int $runIndex) use ($forecastOffset, $scale): array {
                $color = $this->forecastColor($runIndex);
                $points = collect($forecast->point_forecast ?? [])
                    ->map(function (float|int|string $rawValue, int $index) use ($forecast, $forecastOffset, $scale, $color): array {
                        $value = (float) $rawValue;
                        $step = $index + 1;
                        $position = $scale($value, $forecastOffset + $step);
                        $targetTime = $forecast->input_ends_at
                            ? CryptoIntervals::addSteps($forecast->input_ends_at, (string) $forecast->interval, $step)
                            : null;
                        $quantiles = $forecast->quantile_forecast[$index] ?? [];

                        return [
                            ...$position,
                            'title' => ucfirst((string) $forecast->source).' analysis',
                            'value' => $this->price($value),
                            'raw_value' => $value,
                            'time' => $targetTime?->format('Y-m-d H:i:s') ?? "Step {$step}",
                            'color' => $color,
                            'rows' => [
                                ['label' => 'Type', 'value' => 'Analysis point'],
                                ['label' => 'Analyzer', 'value' => (string) $forecast->source],
                                ['label' => 'Step', 'value' => (string) $step],
                                ['label' => 'Target time', 'value' => $targetTime?->format('Y-m-d H:i:s') ?? 'Pending'],
                                ['label' => 'Predicted', 'value' => $this->price($value)],
                                ['label' => 'Low quantile', 'value' => isset($quantiles[0]) ? $this->price($quantiles[0]) : 'n/a'],
                                ['label' => 'Median quantile', 'value' => isset($quantiles[1]) ? $this->price($quantiles[1]) : 'n/a'],
                                ['label' => 'High quantile', 'value' => isset($quantiles[2]) ? $this->price($quantiles[2]) : 'n/a'],
                                ['label' => 'Forecast run', 'value' => '#'.$forecast->getKey()],
                            ],
                        ];
                    })
                    ->values();
                $firstPoint = $points->first();
                $lastPoint = $points->last();

                return [
                    'label' => (string) $forecast->source,
                    'color' => $color,
                    'points' => $points->all(),
                    'polyline' => $this->polyline($points),
                    'point_count' => $points->count(),
                    'first_value' => $firstPoint ? (string) $firstPoint['value'] : 'n/a',
                    'last_value' => $lastPoint ? (string) $lastPoint['value'] : 'n/a',
                    'delta' => $firstPoint && $lastPoint
                        ? $this->signedDelta((float) $firstPoint['raw_value'], (float) $lastPoint['raw_value'])
                        : 'n/a',
                    'target_window' => $forecast->target_starts_at && $forecast->target_ends_at
                        ? $forecast->target_starts_at->format('H:i').' - '.$forecast->target_ends_at->format('H:i')
                        : 'pending',
                    'compared' => (int) $forecast->evaluated_points.'/'.(int) $forecast->total_points,
                    'mape' => $forecast->mean_absolute_percentage_error
                        ? number_format((float) $forecast->mean_absolute_percentage_error, 2).'%'
                        : 'pending',
                    'direction_accuracy' => $forecast->direction_accuracy
                        ? number_format((float) $forecast->direction_accuracy, 2).'%'
                        : 'pending',
                ];
            })
            ->filter(fn (array $series): bool => $series['polyline'] !== '')
            ->values()
            ->all();
        $forecastPoints = $forecastSeries[0]['polyline'] ?? '';

        return [
            'history' => $historyPoints,
            'forecast' => $forecastPoints,
            'forecast_series' => $forecastSeries,
            'min' => $min,
            'max' => $max,
            'latest' => $historyValues->last(),
            'scale_ticks' => $this->scaleTicks($min, $max, $range, $padding, $usableHeight),
            'summary_cards' => $this->summaryCards($min, $max, $historyValues->last(), $historyMeta->count(), $forecastSeries, $historyMeta->last()),
            'latest_marker' => $historyMeta->last(),
            'forecast_labels' => $this->forecastLabels($forecastSeries),
            'point_ledger' => $this->pointLedger($historyMeta->all(), $forecastSeries),
            'tooltip' => $this->payload($historyMeta->all(), $forecastSeries),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $historyPoints
     * @param  array<int, array<string, mixed>>  $forecastSeries
     * @return array<string, mixed>
     */
    private function payload(array $historyPoints, array $forecastSeries): array
    {
        return [
            'point_count' => count($historyPoints) + collect($forecastSeries)->sum(fn (array $series): int => count($series['points'])),
            'series' => collect([
                [
                    'label' => 'Market',
                    'color' => '#2dd4bf',
                    'points' => $historyPoints,
                ],
            ])
                ->merge(collect($forecastSeries)->map(fn (array $series): array => [
                    'label' => $series['label'],
                    'color' => $series['color'],
                    'points' => $series['points'],
                ]))
                ->values()
                ->all(),
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
     * @return array<int, array<string, mixed>>
     */
    private function scaleTicks(float $min, float $max, float $range, int $padding, int $usableHeight): array
    {
        return collect(range(0, 4))
            ->map(function (int $index) use ($max, $range, $padding, $usableHeight): array {
                $ratio = $index / 4;

                return [
                    'y' => round($padding + ($usableHeight * $ratio), 2),
                    'label' => $this->price($max - ($range * $ratio)),
                    'value' => $max - ($range * $ratio),
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $forecastSeries
     * @param  array<string, mixed>|null  $latestMarker
     * @return array<int, array<string, string>>
     */
    private function summaryCards(float $min, float $max, mixed $latest, int $marketPoints, array $forecastSeries, ?array $latestMarker): array
    {
        $forecastPointCount = collect($forecastSeries)->sum(fn (array $series): int => count($series['points']));
        $spread = $max - $min;
        $spreadPercent = $min !== 0.0 ? (($spread / abs($min)) * 100) : 0.0;

        return [
            [
                'label' => 'Latest',
                'value' => $this->price($latest),
                'detail' => (string) ($latestMarker['time'] ?? 'waiting'),
            ],
            [
                'label' => 'Visible high',
                'value' => $this->price($max),
                'detail' => 'top of range',
            ],
            [
                'label' => 'Visible low',
                'value' => $this->price($min),
                'detail' => 'bottom of range',
            ],
            [
                'label' => 'Spread',
                'value' => $this->price($spread),
                'detail' => number_format($spreadPercent, 2).'%',
            ],
            [
                'label' => 'Market points',
                'value' => number_format($marketPoints),
                'detail' => 'candles plus live',
            ],
            [
                'label' => 'Analysis points',
                'value' => number_format($forecastPointCount),
                'detail' => count($forecastSeries).' engines',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $forecastSeries
     * @return array<int, array<string, mixed>>
     */
    private function forecastLabels(array $forecastSeries): array
    {
        $seriesCount = max(count($forecastSeries), 1);

        return collect($forecastSeries)
            ->map(function (array $series, int $index) use ($seriesCount): ?array {
                $lastPoint = collect($series['points'])->last();

                if (! $lastPoint) {
                    return null;
                }

                $offset = ($index - (($seriesCount - 1) / 2)) * 8;

                return [
                    'x' => min(max((float) $lastPoint['x'] + 7, 24), 616),
                    'y' => min(max((float) $lastPoint['y'] + $offset, 26), 236),
                    'label' => (string) $series['label'],
                    'value' => (string) $lastPoint['value'],
                    'color' => (string) $series['color'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $historyPoints
     * @param  array<int, array<string, mixed>>  $forecastSeries
     * @return array<int, array<string, string>>
     */
    private function pointLedger(array $historyPoints, array $forecastSeries): array
    {
        $marketRows = collect($historyPoints)
            ->map(fn (array $point): array => $this->ledgerRow('Market', '#22d3ee', $point));

        $analysisRows = collect($forecastSeries)
            ->flatMap(fn (array $series): Collection => collect($series['points'])
                ->map(fn (array $point): array => $this->ledgerRow((string) $series['label'], (string) $series['color'], $point)));

        return $marketRows
            ->merge($analysisRows)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $point
     * @return array<string, string>
     */
    private function ledgerRow(string $series, string $color, array $point): array
    {
        $rows = collect($point['rows'] ?? [])
            ->mapWithKeys(fn (array $row): array => [(string) $row['label'] => (string) $row['value']]);
        $type = (string) $rows->get('Type', $point['title'] ?? $series);

        if ($type === 'Analysis point') {
            $detail = 'Step '.$rows->get('Step', 'n/a').' -> '.$rows->get('Target time', 'pending');
            $metrics = 'Q '.$rows->get('Low quantile', 'n/a').' / '.$rows->get('Median quantile', 'n/a').' / '.$rows->get('High quantile', 'n/a');
        } elseif ($type === 'Live price') {
            $detail = 'Bid '.$rows->get('Bid', 'n/a').' / Ask '.$rows->get('Ask', 'n/a');
            $metrics = '24h '.$rows->get('24h change', 'n/a').' / Vol '.$rows->get('Quote volume', 'n/a');
        } else {
            $detail = 'O '.$rows->get('Open', 'n/a').' / H '.$rows->get('High', 'n/a').' / L '.$rows->get('Low', 'n/a');
            $metrics = 'Vol '.$rows->get('Quote volume', 'n/a').' / Trades '.$rows->get('Trades', 'n/a');
        }

        return [
            'series' => $series,
            'color' => $color,
            'type' => $type,
            'time' => (string) ($point['time'] ?? 'pending'),
            'value' => (string) ($point['value'] ?? 'n/a'),
            'detail' => $detail,
            'metrics' => $metrics,
        ];
    }

    private function price(float|int|string|null $value): string
    {
        if ($value === null) {
            return 'n/a';
        }

        $number = (float) $value;

        return number_format($number, abs($number) >= 1 ? 2 : 8);
    }

    private function number(float|int|string|null $value): string
    {
        return $value === null ? 'n/a' : number_format((float) $value, 2);
    }

    private function percent(float|int|string|null $value): string
    {
        return $value === null ? 'n/a' : number_format((float) $value, 2).'%';
    }

    private function signedDelta(float $first, float $last): string
    {
        $delta = $last - $first;
        $percent = $first !== 0.0 ? ($delta / abs($first)) * 100 : 0.0;

        return ($delta >= 0 ? '+' : '').$this->price($delta).' / '.($percent >= 0 ? '+' : '').number_format($percent, 2).'%';
    }

    private function forecastColor(int $index): string
    {
        return [
            '#fbbf24',
            '#a78bfa',
            '#fb7185',
            '#34d399',
            '#60a5fa',
            '#f97316',
        ][$index % 6];
    }
}
