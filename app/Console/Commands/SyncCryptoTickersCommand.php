<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FetchBinanceTickersAction;
use App\Actions\Crypto\WarmCryptoDashboardCacheAction;
use Illuminate\Console\Command;

class SyncCryptoTickersCommand extends Command
{
    protected $signature = 'crypto:sync-tickers {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Fetch the latest Binance mini ticker snapshots for configured crypto symbols.';

    public function handle(FetchBinanceTickersAction $tickers, WarmCryptoDashboardCacheAction $cache): int
    {
        $symbols = array_slice(config('crypto.binance.symbols', []), 0, (int) $this->option('limit'));
        $summary = $tickers->handle($symbols);
        $warmed = ['reads' => 0];

        if ((bool) config('crypto.cache.warm_after_ticker_sync', true)) {
            $warmed = $cache->handle($symbols, null, (int) config('crypto.cache.warm_limit', 3));
        }

        $this->info("Stored {$summary['snapshots']} crypto ticker snapshots; warmed {$warmed['reads']} dashboard reads.");

        return self::SUCCESS;
    }
}
