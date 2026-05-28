<?php

namespace App\Console\Commands;

use App\Actions\Crypto\FillMissingCryptoCandlesAction;
use App\Actions\Crypto\RunTimesFmForecastAction;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use Illuminate\Console\Command;
use Throwable;

class RunCryptoForecastCycleCommand extends Command
{
    protected $signature = 'crypto:forecast-cycle
        {period=15m : Forecast period configured in config/crypto.php}
        {--limit=3 : Number of top active assets to forecast}
        {--fresh-minutes=5 : Skip assets with a newer completed forecast}';

    protected $description = 'Run a lightweight forecast cycle for top crypto assets and store forecast points for later scoring.';

    public function handle(FillMissingCryptoCandlesAction $fillMissing, RunTimesFmForecastAction $forecasts): int
    {
        $period = (string) $this->argument('period');
        $settings = config("crypto.forecasting.periods.{$period}");

        if (! is_array($settings)) {
            $this->error("Unknown forecast period [{$period}].");

            return self::FAILURE;
        }

        $assets = CryptoAsset::query()
            ->dashboardList((int) $this->option('limit'))
            ->get();
        $stored = 0;

        foreach ($assets as $asset) {
            $latest = CryptoForecast::query()
                ->forAsset($asset)
                ->forInterval((string) $settings['interval'])
                ->completed()
                ->where('completed_at', '>=', now()->subMinutes((int) $this->option('fresh-minutes')))
                ->orderByDesc('completed_at')
                ->first();

            if ($latest) {
                continue;
            }

            try {
                $fillMissing->handle(
                    [$asset->symbol],
                    [(string) $settings['interval']],
                    (int) $settings['context'],
                );

                $forecast = $forecasts->handle(
                    $asset,
                    (string) $settings['interval'],
                    (int) $settings['horizon'],
                    (int) $settings['context'],
                );

                $this->line("Stored forecast #{$forecast->getKey()} for {$asset->symbol}.");
                $stored++;
            } catch (Throwable $exception) {
                $this->warn("{$asset->symbol}: {$exception->getMessage()}");
            }
        }

        $this->info("Stored {$stored} new {$period} forecasts.");

        return self::SUCCESS;
    }
}
