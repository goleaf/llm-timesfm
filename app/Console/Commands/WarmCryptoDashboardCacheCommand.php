<?php

namespace App\Console\Commands;

use App\Actions\Crypto\WarmCryptoDashboardCacheAction;
use App\Http\Requests\Crypto\WarmCryptoDashboardCacheRequest;
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
        $request = WarmCryptoDashboardCacheRequest::fromConsole(
            $this->option('symbol'),
            $this->option('interval'),
            $this->option('limit'),
        );
        $summary = $cache->handle(
            $request->symbols,
            $request->intervals,
            $request->limit,
        );

        $this->info(
            "Warmed {$summary['reads']} dashboard reads ".
            "for {$summary['symbols']} symbols / {$summary['intervals']} intervals.",
        );

        return self::SUCCESS;
    }
}
