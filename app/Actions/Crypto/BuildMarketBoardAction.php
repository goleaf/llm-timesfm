<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
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
            'bid' => $this->price($snapshot?->bid_price),
            'ask' => $this->price($snapshot?->ask_price),
            'high' => $this->price($snapshot?->high_price),
            'low' => $this->price($snapshot?->low_price),
            'quote_volume' => $this->number($snapshot?->quote_volume, 0),
            'trades' => number_format((int) ($snapshot?->trade_count ?? 0)),
            'change' => $this->signedPercent($snapshot?->price_change_percent ?? $change),
            'change_positive' => (float) ($snapshot?->price_change_percent ?? $change) >= 0,
            'updated_at' => $snapshot?->source_event_at?->format('H:i:s') ?? 'waiting',
            'updated_full' => $snapshot?->source_event_at?->toDayDateTimeString() ?? 'Waiting for live data',
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
