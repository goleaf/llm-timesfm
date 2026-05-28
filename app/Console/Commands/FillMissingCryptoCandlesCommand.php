<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FillMissingCryptoCandlesAction;
use Illuminate\Console\Command;

class FillMissingCryptoCandlesCommand extends Command
{
    protected $signature = 'crypto:fill-missing-candles
        {symbol? : Optional symbol such as BTCUSDT}
        {--interval=* : Short interval to keep filled, for example 1m or 5m}
        {--window=720 : Recent candle window to inspect for gaps}';

    protected $description = 'Fetch only missing Binance kline candles for short realtime intervals.';

    public function handle(FillMissingCryptoCandlesAction $fillMissing): int
    {
        $symbol = $this->argument('symbol');
        $intervals = array_values(array_filter((array) $this->option('interval')));
        $summary = $fillMissing->handle(
            $symbol ? [(string) $symbol] : null,
            $intervals === [] ? null : $intervals,
            (int) $this->option('window'),
        );

        $this->info(
            "Checked {$summary['assets']} assets / {$summary['intervals']} intervals; ".
            "sent {$summary['requests']} requests; stored {$summary['candles']} candles.",
        );

        return self::SUCCESS;
    }
}
