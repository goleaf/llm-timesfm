<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FetchBinanceTickersAction;
use Illuminate\Console\Command;
use Throwable;

class WatchCryptoTickersCommand extends Command
{
    protected $signature = 'crypto:watch-tickers {--seconds=0 : Seconds to run; 0 keeps running} {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Poll Binance ticker snapshots every configured second for the realtime dashboard.';

    public function handle(FetchBinanceTickersAction $tickers): int
    {
        $seconds = (int) $this->option('seconds');
        $limit = (int) $this->option('limit');
        $pollSeconds = max((int) config('crypto.binance.poll_seconds'), 1);
        $startedAt = time();
        $symbols = array_slice(config('crypto.binance.symbols', []), 0, $limit);

        do {
            try {
                $summary = $tickers->handle($symbols);
                $this->line(now()->format('H:i:s')." stored {$summary['snapshots']} snapshots");
            } catch (Throwable $exception) {
                $this->warn(now()->format('H:i:s').' '.$exception->getMessage());
            }

            if ($seconds > 0 && (time() - $startedAt) >= $seconds) {
                break;
            }

            sleep($pollSeconds);
        } while (true);

        return self::SUCCESS;
    }
}
