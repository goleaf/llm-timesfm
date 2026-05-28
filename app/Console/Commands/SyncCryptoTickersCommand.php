<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FetchBinanceTickersAction;
use Illuminate\Console\Command;

class SyncCryptoTickersCommand extends Command
{
    protected $signature = 'crypto:sync-tickers {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Fetch the latest Binance mini ticker snapshots for configured crypto symbols.';

    public function handle(FetchBinanceTickersAction $tickers): int
    {
        $symbols = array_slice(config('crypto.binance.symbols', []), 0, (int) $this->option('limit'));
        $summary = $tickers->handle($symbols);

        $this->info("Stored {$summary['snapshots']} crypto ticker snapshots.");

        return self::SUCCESS;
    }
}
