<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoPriceSnapshot;
use Illuminate\Support\Collection;

class BuildMarketBoardAction
{
    /**
     * @param  Collection<int, CryptoAsset>  $assets
     * @param  array<int, string>  $pinnedSymbols
     * @return array{
     *     rows:Collection<int,array<string,mixed>>,
     *     pinned:Collection<int,array<string,mixed>>,
     *     filtered:Collection<int,array<string,mixed>>,
     *     selected:?array<string,mixed>,
     *     base_options:Collection<int,array<string,mixed>>,
     *     quote_suggestions:Collection<int,string>
     * }
     */
    public function handle(
        Collection $assets,
        array $pinnedSymbols,
        string $baseSearch,
        string $quoteSearch,
        ?CryptoAsset $selectedAsset = null,
    ): array {
        $pinnedSymbols = collect($pinnedSymbols)
            ->map(fn (string $symbol): string => strtoupper($symbol))
            ->unique()
            ->values();

        $rows = $assets
            ->map(fn (CryptoAsset $asset): array => $this->row($asset, $pinnedSymbols->contains($asset->symbol), $selectedAsset?->is($asset) ?? false))
            ->values();

        $filtered = $rows
            ->filter(fn (array $row): bool => $this->matches($row, $baseSearch, $quoteSearch))
            ->sortBy([
                fn (array $left, array $right): int => (int) $right['is_pinned'] <=> (int) $left['is_pinned'],
                fn (array $left, array $right): int => $left['rank'] <=> $right['rank'],
            ])
            ->values();

        return [
            'rows' => $rows,
            'pinned' => $rows->where('is_pinned', true)->values(),
            'filtered' => $filtered,
            'selected' => $rows->firstWhere('is_selected', true),
            'base_options' => $this->baseOptions($rows, $baseSearch, $quoteSearch),
            'quote_suggestions' => $this->suggestions($rows, 'quote_asset', $quoteSearch),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(CryptoAsset $asset, bool $isPinned, bool $isSelected): array
    {
        $snapshot = $asset->latestSnapshot;
        $openPrice = (float) ($snapshot?->open_price ?? 0);
        $lastPrice = (float) ($snapshot?->price ?? 0);
        $change = $openPrice > 0 ? (($lastPrice - $openPrice) / $openPrice) * 100 : 0.0;

        return [
            'symbol' => $asset->symbol,
            'display_pair' => $asset->display_pair,
            'base_asset' => $asset->base_asset,
            'quote_asset' => $asset->quote_asset,
            'rank' => (int) $asset->rank,
            'is_pinned' => $isPinned,
            'is_selected' => $isSelected,
            'price' => $this->price($snapshot?->price),
            'bid' => $this->nullablePrice($snapshot?->bid_price),
            'ask' => $this->nullablePrice($snapshot?->ask_price),
            'high' => $this->price($snapshot?->high_price),
            'low' => $this->price($snapshot?->low_price),
            'quote_volume' => $this->number($snapshot?->quote_volume, 0),
            'trades' => number_format((int) ($snapshot?->trade_count ?? 0)),
            'change' => $this->signedPercent($snapshot?->price_change_percent ?? $change),
            'change_positive' => (float) ($snapshot?->price_change_percent ?? $change) >= 0,
            'pulse_chart' => $this->pulseChart($snapshot),
            'updated_at' => $snapshot?->source_event_at?->format('H:i:s') ?? 'waiting',
            'updated_full' => $snapshot?->source_event_at?->toDayDateTimeString() ?? __('ui.common.waiting_for_live_data'),
        ];
    }

    /**
     * @return array{
     *     has_data:bool,
     *     points:string,
     *     baseline_y:float,
     *     range_top:float,
     *     range_height:float,
     *     current_x:float,
     *     current_y:float,
     *     stroke:string
     * }
     */
    private function pulseChart(?CryptoPriceSnapshot $snapshot): array
    {
        $open = (float) ($snapshot?->open_price ?? 0);
        $last = (float) ($snapshot?->price ?? 0);
        $high = (float) ($snapshot?->high_price ?? 0);
        $low = (float) ($snapshot?->low_price ?? 0);
        $middle = (float) ($snapshot?->weighted_avg_price ?? 0);
        $middle = $middle > 0 ? $middle : (($open > 0 && $last > 0) ? ($open + $last) / 2 : max($open, $last));
        $values = collect([$open, $middle, $last, $high, $low])
            ->filter(fn (float $value): bool => $value > 0)
            ->values();

        if ($values->isEmpty()) {
            return [
                'has_data' => false,
                'points' => '',
                'baseline_y' => 20.0,
                'range_top' => 18.0,
                'range_height' => 4.0,
                'current_x' => 112.0,
                'current_y' => 20.0,
                'stroke' => '#a1a1aa',
            ];
        }

        $floor = (float) $values->min();
        $ceiling = (float) $values->max();
        $spread = max($ceiling - $floor, abs($ceiling) * 0.001, 1.0);
        $scale = fn (float $value): float => round(34 - (($value - $floor) / $spread) * 28, 2);
        $points = [
            [6.0, $scale($open > 0 ? $open : $floor)],
            [42.0, $scale($middle)],
            [78.0, $scale($last > 0 ? $last : $middle)],
            [114.0, $scale($last > 0 ? $last : $middle)],
        ];

        return [
            'has_data' => true,
            'points' => collect($points)
                ->map(fn (array $point): string => $point[0].','.$point[1])
                ->implode(' '),
            'baseline_y' => $scale($open > 0 ? $open : $floor),
            'range_top' => $scale($ceiling),
            'range_height' => max(2.0, $scale($floor) - $scale($ceiling)),
            'current_x' => 114.0,
            'current_y' => $scale($last > 0 ? $last : $middle),
            'stroke' => $last >= $open ? '#34d399' : '#fb7185',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function matches(array $row, string $baseSearch, string $quoteSearch): bool
    {
        $baseSearch = strtoupper(trim($baseSearch));
        $quoteSearch = strtoupper(trim($quoteSearch));

        if ($baseSearch !== '' && ! str_contains((string) $row['base_asset'], $baseSearch) && ! str_contains((string) $row['symbol'], $baseSearch)) {
            return false;
        }

        if ($quoteSearch !== '' && ! str_contains((string) $row['quote_asset'], $quoteSearch) && ! str_contains((string) $row['symbol'], $quoteSearch)) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function baseOptions(Collection $rows, string $baseSearch, string $quoteSearch): Collection
    {
        return $rows
            ->filter(fn (array $row): bool => $this->matches($row, $baseSearch, $quoteSearch))
            ->groupBy('base_asset')
            ->map(function (Collection $baseRows, string $baseAsset): array {
                $topRow = $baseRows
                    ->sortBy([
                        fn (array $left, array $right): int => (int) $right['is_pinned'] <=> (int) $left['is_pinned'],
                        fn (array $left, array $right): int => (int) $left['rank'] <=> (int) $right['rank'],
                    ])
                    ->first();

                return [
                    'asset' => $baseAsset,
                    'market_count' => $baseRows->count(),
                    'quote_assets' => $baseRows->pluck('quote_asset')->unique()->take(4)->implode('/'),
                    'pin_symbol' => (string) $topRow['symbol'],
                    'pin_pair' => (string) $topRow['display_pair'],
                    'is_pinned' => (bool) $topRow['is_pinned'],
                    'is_selected' => (bool) $topRow['is_selected'],
                    'price' => (string) $topRow['price'],
                    'change' => (string) $topRow['change'],
                    'change_positive' => (bool) $topRow['change_positive'],
                    'rank' => (int) $topRow['rank'],
                ];
            })
            ->sortBy([
                fn (array $left, array $right): int => (int) $right['is_selected'] <=> (int) $left['is_selected'],
                fn (array $left, array $right): int => (int) $right['is_pinned'] <=> (int) $left['is_pinned'],
                fn (array $left, array $right): int => (int) $left['rank'] <=> (int) $right['rank'],
            ])
            ->take(24)
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, string>
     */
    private function suggestions(Collection $rows, string $key, string $search): Collection
    {
        $search = strtoupper(trim($search));

        return $rows
            ->pluck($key)
            ->filter(fn (string $asset): bool => $search === '' || str_contains($asset, $search))
            ->unique()
            ->take(8)
            ->values();
    }

    private function price(float|int|string|null $value): string
    {
        if ($value === null) {
            return '0.00';
        }

        $number = (float) $value;

        return number_format($number, abs($number) >= 1 ? 2 : 8);
    }

    private function nullablePrice(float|int|string|null $value): string
    {
        if ($value === null || (float) $value <= 0) {
            return __('ui.common.na');
        }

        return $this->price($value);
    }

    private function number(float|int|string|null $value, int $decimals = 2): string
    {
        return number_format((float) ($value ?? 0), $decimals);
    }

    private function signedPercent(float|int|string|null $value): string
    {
        $number = (float) ($value ?? 0);

        return ($number >= 0 ? '+' : '').number_format($number, 2).'%';
    }
}
