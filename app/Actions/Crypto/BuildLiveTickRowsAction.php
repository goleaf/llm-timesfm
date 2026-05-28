<?php

namespace App\Actions\Crypto;

use App\Models\CryptoPriceSnapshot;
use Illuminate\Support\Collection;

class BuildLiveTickRowsAction
{
    /**
     * @param  Collection<int, CryptoPriceSnapshot>  $snapshots
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(Collection $snapshots): Collection
    {
        $snapshots = $snapshots->values();

        return $snapshots
            ->map(function (CryptoPriceSnapshot $snapshot, int $index) use ($snapshots): array {
                $movement = $this->movement($snapshot, $snapshots->get($index + 1));

                return [
                    'id' => $snapshot->getKey(),
                    'time' => $snapshot->source_event_at?->format('H:i:s') ?? __('ui.common.waiting'),
                    'price' => $this->price($snapshot->price),
                    'change' => $movement['value'],
                    'change_positive' => $movement['positive'],
                    'range' => __('ui.market.tick_range', [
                        'high' => $this->price($snapshot->high_price),
                        'low' => $this->price($snapshot->low_price),
                    ]),
                    'activity' => __('ui.market.tick_activity', [
                        'volume' => $this->compactNumber($snapshot->quote_volume),
                        'trades' => $this->compactNumber($snapshot->trade_count),
                    ]),
                ];
            })
            ->values();
    }

    /**
     * @return array{value:string,positive:bool}
     */
    private function movement(CryptoPriceSnapshot $snapshot, ?CryptoPriceSnapshot $previous): array
    {
        if ($snapshot->price_change_percent !== null) {
            $percent = (float) $snapshot->price_change_percent;

            return [
                'value' => ($percent >= 0 ? '+' : '').number_format($percent, 2).'%',
                'positive' => $percent >= 0,
            ];
        }

        $currentPrice = (float) ($snapshot->price ?? 0);
        $previousPrice = (float) ($previous?->price ?? 0);

        if ($currentPrice <= 0 || $previousPrice <= 0) {
            return [
                'value' => __('ui.common.na'),
                'positive' => true,
            ];
        }

        $delta = $currentPrice - $previousPrice;
        $decimals = abs($currentPrice) >= 1 ? 2 : 8;

        return [
            'value' => ($delta > 0 ? '+' : '').number_format($delta, $decimals),
            'positive' => $delta >= 0,
        ];
    }

    private function price(float|int|string|null $value): string
    {
        if ($value === null || (float) $value <= 0) {
            return __('ui.common.na');
        }

        $number = (float) $value;

        return number_format($number, abs($number) >= 1 ? 2 : 8);
    }

    private function compactNumber(float|int|string|null $value): string
    {
        if ($value === null) {
            return __('ui.common.na');
        }

        $number = (float) $value;
        $absolute = abs($number);

        if ($absolute >= 1_000_000_000) {
            return number_format($number / 1_000_000_000, 2).'B';
        }

        if ($absolute >= 1_000_000) {
            return number_format($number / 1_000_000, 2).'M';
        }

        if ($absolute >= 1_000) {
            return number_format($number / 1_000, 2).'K';
        }

        return number_format($number, $number >= 1 ? 0 : 8);
    }
}
