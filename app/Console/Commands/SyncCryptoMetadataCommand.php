<?php

namespace App\Console\Commands;

use App\Actions\Crypto\SyncConfiguredCryptoMetadataAction;
use App\Http\Requests\Crypto\SyncCryptoMetadataRequest;
use Illuminate\Console\Command;

class SyncCryptoMetadataCommand extends Command
{
    protected $signature = 'crypto:sync-metadata {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Fetch Binance exchangeInfo metadata for configured crypto symbols.';

    public function handle(SyncConfiguredCryptoMetadataAction $metadata): int
    {
        $request = SyncCryptoMetadataRequest::fromConsole($this->option('limit'));
        $summary = $metadata->handle($request);

        $this->info("Stored metadata for {$summary['assets']} crypto assets.");

        return self::SUCCESS;
    }
}
