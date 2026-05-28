<?php

namespace App\Console\Commands;

use App\Actions\Crypto\SyncConfiguredCryptoTickersAction;
use App\Http\Requests\Crypto\SyncCryptoTickersRequest;
use Illuminate\Console\Command;

class SyncCryptoTickersCommand extends Command
{
    protected $signature = 'crypto:sync-tickers {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Fetch the latest Binance mini ticker snapshots for configured crypto symbols.';

    public function handle(SyncConfiguredCryptoTickersAction $tickers): int
    {
        $request = SyncCryptoTickersRequest::fromConsole($this->option('limit'));
        $summary = $tickers->handle($request);

        $this->info("Stored {$summary['snapshots']} crypto ticker snapshots; warmed {$summary['warmed_reads']} dashboard reads.");

        return self::SUCCESS;
    }
}
