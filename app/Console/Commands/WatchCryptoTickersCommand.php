<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FetchBinanceTickersAction;
use App\Actions\Crypto\WarmCryptoDashboardCacheAction;
use Illuminate\Console\Command;
use Throwable;

class WatchCryptoTickersCommand extends Command
{
    protected $signature = 'crypto:watch-tickers {--seconds=0 : Seconds to run; 0 keeps running} {--limit=20 : Number of configured symbols to request}';

    protected $description = 'Poll Binance ticker snapshots every configured second for the realtime dashboard.';

    public function handle(FetchBinanceTickersAction $tickers, WarmCryptoDashboardCacheAction $cache): int
    {
        $seconds = (int) $this->option('seconds');
        $limit = (int) $this->option('limit');
        $pollSeconds = max((int) config('crypto.binance.poll_seconds'), 1);
        $startedAt = time();
        $symbols = array_slice(config('crypto.binance.symbols', []), 0, $limit);

        do {
            try {
                $summary = $tickers->handle($symbols);
                $warmed = ['reads' => 0];

                if ((bool) config('crypto.cache.warm_after_ticker_sync', true)) {
                    $warmed = $cache->handle($symbols, null, (int) config('crypto.cache.warm_limit', 3));
                }

                $this->line(now()->format('H:i:s')." stored {$summary['snapshots']} snapshots; warmed {$warmed['reads']} reads");
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
