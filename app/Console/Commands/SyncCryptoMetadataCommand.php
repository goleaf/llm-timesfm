<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FetchBinanceExchangeInfoAction;
use Illuminate\Console\Command;

class SyncCryptoMetadataCommand extends Command
{
    protected $signature = 'crypto:sync-metadata {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Fetch Binance exchangeInfo metadata for configured crypto symbols.';

    public function handle(FetchBinanceExchangeInfoAction $metadata): int
    {
        $symbols = array_slice(config('crypto.binance.symbols', []), 0, (int) $this->option('limit'));
        $summary = $metadata->handle($symbols);

        $this->info("Stored metadata for {$summary['assets']} crypto assets.");

        return self::SUCCESS;
    }
}
