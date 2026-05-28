<?php

namespace App\Console\Commands;

use App\Actions\Crypto\WarmCryptoDashboardCacheAction;
use Illuminate\Console\Command;

class WarmCryptoDashboardCacheCommand extends Command
{
    protected $signature = 'crypto:warm-dashboard-cache
        {--limit=3 : Number of configured symbols to warm}
        {--symbol=* : Symbol to warm, for example BTCUSDT}
        {--interval=* : Interval to warm, for example 1m or 5m}';

    protected $description = 'Warm the hot Livewire dashboard cache for realtime market and forecast screens.';

    public function handle(WarmCryptoDashboardCacheAction $cache): int
    {
        $symbols = array_values(array_filter((array) $this->option('symbol')));
        $intervals = array_values(array_filter((array) $this->option('interval')));
        $summary = $cache->handle(
            $symbols === [] ? null : $symbols,
            $intervals === [] ? null : $intervals,
            (int) $this->option('limit'),
        );

        $this->info(
            "Warmed {$summary['reads']} dashboard reads ".
            "for {$summary['symbols']} symbols / {$summary['intervals']} intervals.",
        );

        return self::SUCCESS;
    }
}
