<?php

namespace App\Actions\Crypto;

class WarmCryptoDashboardCacheAction
{
    public function __construct(
        private readonly ReadMarketsDashboardAction $markets,
        private readonly ReadForecastStatsDashboardAction $stats,
    ) {}

    /**
     * @param  array<int, string>|null  $symbols
     * @param  array<int, string>|null  $intervals
     * @return array{symbols:int,intervals:int,reads:int}
     */
    public function handle(?array $symbols = null, ?array $intervals = null, ?int $limit = null): array
    {
        $limit ??= (int) config('crypto.cache.warm_limit', 3);
        $symbols = array_slice(
            array_values(array_filter($symbols ?: config('crypto.cache.warm_symbols', []))),
            0,
            max($limit, 1),
        );
        $intervals = array_values(array_filter($intervals ?: config('crypto.cache.warm_intervals', ['1m'])));
        $reads = 0;

        foreach ($symbols as $symbol) {
            foreach ($intervals as $interval) {
                $this->markets->handle((string) $symbol, (string) $interval);
                $this->stats->handle((string) $symbol, (string) $interval);
                $reads += 2;
            }
        }

        return [
            'symbols' => count($symbols),
            'intervals' => count($intervals),
            'reads' => $reads,
        ];
    }
}
