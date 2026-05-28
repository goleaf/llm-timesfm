<?php

namespace App\Console\Commands;

use App\Actions\Crypto\WatchCryptoTickersAction;
use App\Http\Requests\Crypto\WatchCryptoTickersRequest;
use Illuminate\Console\Command;

class WatchCryptoTickersCommand extends Command
{
    protected $signature = 'crypto:watch-tickers {--seconds=0 : Seconds to run; 0 keeps running} {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Poll Binance ticker snapshots every configured second for the realtime dashboard.';

    public function handle(WatchCryptoTickersAction $watch): int
    {
        $request = WatchCryptoTickersRequest::fromConsole($this->option('seconds'), $this->option('limit'));
        $watch->handle(
            $request,
            function (string $message): void {
                $this->line($message);
            },
            function (string $message): void {
                $this->warn($message);
            },
        );

        return self::SUCCESS;
    }
}
