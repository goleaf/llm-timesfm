<?php

namespace App\Actions\Crypto;

use App\Models\CryptoPriceSnapshot;
use Illuminate\Support\Collection;

class BuildSnapshotHistoryRowsAction
{
    /**
     * @param  Collection<int, CryptoPriceSnapshot>  $snapshots
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(Collection $snapshots): Collection
    {
        return $snapshots
            ->map(fn (CryptoPriceSnapshot $snapshot): array => [
                'id' => $snapshot->getKey(),
                'time' => $snapshot->source_event_at->format('H:i:s'),
                'date' => $snapshot->source_event_at->format('Y-m-d H:i:s'),
                'price' => $this->price($snapshot->price),
                'change' => $this->signedPercent($snapshot->price_change_percent),
                'change_positive' => (float) ($snapshot->price_change_percent ?? 0) >= 0,
                'summary' => [
                    ['label' => 'Last', 'value' => $this->price($snapshot->price)],
                    ['label' => 'Bid / Ask', 'value' => $this->price($snapshot->bid_price).' / '.$this->price($snapshot->ask_price)],
                    ['label' => 'Spread', 'value' => $this->spread($snapshot)],
                    ['label' => 'Quote volume', 'value' => $this->number($snapshot->quote_volume, 0)],
                    ['label' => 'Trades', 'value' => number_format((int) $snapshot->trade_count)],
                    ['label' => 'Source', 'value' => (string) $snapshot->source],
                ],
                'sections' => [
                    [
                        'title' => 'Price',
                        'rows' => [
                            ['label' => 'Open', 'value' => $this->price($snapshot->open_price)],
                            ['label' => 'High', 'value' => $this->price($snapshot->high_price)],
                            ['label' => 'Low', 'value' => $this->price($snapshot->low_price)],
                            ['label' => 'Last', 'value' => $this->price($snapshot->price)],
                            ['label' => 'Change', 'value' => $this->price($snapshot->price_change)],
                            ['label' => 'Change %', 'value' => $this->signedPercent($snapshot->price_change_percent)],
                            ['label' => 'Weighted average', 'value' => $this->price($snapshot->weighted_avg_price)],
                            ['label' => 'Previous close', 'value' => $this->price($snapshot->prev_close_price)],
                        ],
                    ],
                    [
                        'title' => 'Order book',
                        'rows' => [
                            ['label' => 'Bid price', 'value' => $this->price($snapshot->bid_price)],
                            ['label' => 'Bid quantity', 'value' => $this->number($snapshot->bid_qty)],
                            ['label' => 'Ask price', 'value' => $this->price($snapshot->ask_price)],
                            ['label' => 'Ask quantity', 'value' => $this->number($snapshot->ask_qty)],
                            ['label' => 'Last quantity', 'value' => $this->number($snapshot->last_qty)],
                            ['label' => 'Spread', 'value' => $this->spread($snapshot)],
                        ],
                    ],
                    [
                        'title' => 'Volume',
                        'rows' => [
                            ['label' => 'Base volume', 'value' => $this->number($snapshot->base_volume)],
                            ['label' => 'Quote volume', 'value' => $this->number($snapshot->quote_volume)],
                            ['label' => 'Trade count', 'value' => number_format((int) $snapshot->trade_count)],
                        ],
                    ],
                    [
                        'title' => 'Exchange event',
                        'rows' => [
                            ['label' => 'Event time', 'value' => $snapshot->source_event_at->format('Y-m-d H:i:s')],
                            ['label' => 'Open time', 'value' => $snapshot->open_time?->format('Y-m-d H:i:s') ?? 'n/a'],
                            ['label' => 'Close time', 'value' => $snapshot->close_time?->format('Y-m-d H:i:s') ?? 'n/a'],
                            ['label' => 'First trade ID', 'value' => $snapshot->first_trade_id === null ? 'n/a' : number_format((int) $snapshot->first_trade_id)],
                            ['label' => 'Last trade ID', 'value' => $snapshot->last_trade_id === null ? 'n/a' : number_format((int) $snapshot->last_trade_id)],
                        ],
                    ],
                ],
                'payload_rows' => $this->payloadRows($snapshot->raw_payload ?? []),
                'raw_json' => json_encode($snapshot->raw_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{label:string,value:string}>
     */
    private function payloadRows(array $payload): array
    {
        return collect($payload)
            ->map(fn (mixed $value, string|int $key): array => [
                'label' => $this->label((string) $key),
                'value' => $this->rawValue($value),
            ])
            ->values()
            ->all();
    }

    private function label(string $key): string
    {
        $label = (string) preg_replace('/(?<!^)[A-Z]/', ' $0', $key);

        return ucfirst(str_replace('_', ' ', $label));
    }

    private function rawValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES) ?: 'n/a';
    }

    private function spread(CryptoPriceSnapshot $snapshot): string
    {
        if ($snapshot->bid_price === null || $snapshot->ask_price === null) {
            return 'n/a';
        }

        return $this->price((float) $snapshot->ask_price - (float) $snapshot->bid_price);
    }

    private function price(float|int|string|null $value): string
    {
        if ($value === null) {
            return 'n/a';
        }

        $number = (float) $value;

        return number_format($number, abs($number) >= 1 ? 2 : 8);
    }

    private function number(float|int|string|null $value, int $decimals = 2): string
    {
        return $value === null ? 'n/a' : number_format((float) $value, $decimals);
    }

    private function signedPercent(float|int|string|null $value): string
    {
        if ($value === null) {
            return 'n/a';
        }

        $number = (float) $value;

        return ($number >= 0 ? '+' : '').number_format($number, 2).'%';
    }
}
