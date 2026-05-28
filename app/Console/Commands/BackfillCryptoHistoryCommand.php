<?php

namespace App\Console\Commands;

use App\Actions\Crypto\BackfillCryptoHistoryAction;
use App\Http\Requests\Crypto\BackfillCryptoHistoryRequest;
use Illuminate\Console\Command;

class BackfillCryptoHistoryCommand extends Command
{
    protected $signature = 'crypto:backfill-history {symbol? : Symbol such as BTCUSDT} {--interval=1m} {--limit=240}';

    protected $description = 'Fetch recent Binance kline history for one symbol or the active dashboard symbols.';

    public function handle(BackfillCryptoHistoryAction $backfill): int
    {
        $request = BackfillCryptoHistoryRequest::fromConsole(
            $this->argument('symbol'),
            $this->option('interval'),
            $this->option('limit'),
        );

        foreach ($backfill->handle($request) as $result) {
            $this->line("Stored {$result['stored']} {$result['interval']} candles for {$result['symbol']}.");
        }

        return self::SUCCESS;
    }
}
