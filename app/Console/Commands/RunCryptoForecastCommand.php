<?php

namespace App\Console\Commands;

use App\Actions\Crypto\RunTimesFmForecastAction;
use App\Models\CryptoAsset;
use Illuminate\Console\Command;

class RunCryptoForecastCommand extends Command
{
    protected $signature = 'crypto:forecast {symbol=BTCUSDT} {period=1h}';

    protected $description = 'Run a forecast for a crypto symbol using TimesFM when enabled.';

    public function handle(RunTimesFmForecastAction $forecasts): int
    {
        $period = (string) $this->argument('period');
        $settings = config("crypto.forecasting.periods.{$period}");

        if (! is_array($settings)) {
            $this->error("Unknown forecast period [{$period}].");

            return self::FAILURE;
        }

        $asset = CryptoAsset::query()
            ->forSymbol((string) $this->argument('symbol'))
            ->firstOrFail();

        $forecast = $forecasts->handle(
            $asset,
            (string) $settings['interval'],
            (int) $settings['horizon'],
            (int) $settings['context'],
        );

        $this->info("Stored {$forecast->source} forecast #{$forecast->getKey()} for {$asset->symbol}.");

        return self::SUCCESS;
    }
}
