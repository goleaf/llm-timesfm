<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FetchBinanceKlinesAction;
use App\Models\CryptoAsset;
use Illuminate\Console\Command;

class BackfillCryptoHistoryCommand extends Command
{
    protected $signature = 'crypto:backfill-history {symbol? : Symbol such as BTCUSDT} {--interval=1m} {--limit=240}';

    protected $description = 'Fetch recent Binance kline history for one symbol or the active dashboard symbols.';

    public function handle(FetchBinanceKlinesAction $klines): int
    {
        $symbol = $this->argument('symbol');
        $interval = (string) $this->option('interval');
        $limit = (int) $this->option('limit');

        $assets = $symbol
            ? CryptoAsset::query()->forSymbol((string) $symbol)->limit(1)->get()
            : CryptoAsset::query()->dashboardList(20)->get();

        foreach ($assets as $asset) {
            $stored = $klines->handle($asset, $interval, $limit);
            $this->line("Stored {$stored} {$interval} candles for {$asset->symbol}.");
        }

        return self::SUCCESS;
    }
}
