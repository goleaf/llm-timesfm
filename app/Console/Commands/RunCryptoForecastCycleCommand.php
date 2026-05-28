<?php

namespace App\Console\Commands;

use App\Actions\Crypto\RunCryptoForecastCycleAction;
use App\Http\Requests\Crypto\RunCryptoForecastCycleRequest;
use Illuminate\Console\Command;

class RunCryptoForecastCycleCommand extends Command
{
    protected $signature = 'crypto:forecast-cycle
        {period=15m : Forecast period configured in config/crypto.php}
        {--limit=3 : Number of top active assets to forecast}
        {--fresh-minutes=5 : Skip assets with a newer completed forecast}';

    protected $description = 'Run configured forecast analyzers for top crypto assets and store points for later scoring.';

    public function handle(RunCryptoForecastCycleAction $cycle): int
    {
        $request = RunCryptoForecastCycleRequest::fromConsole(
            $this->argument('period'),
            $this->option('limit'),
            $this->option('fresh-minutes'),
        );
        $summary = $cycle->handle(
            $request,
            function (string $message): void {
                $this->line($message);
            },
            function (string $message): void {
                $this->warn($message);
            },
        );

        $this->info("Stored {$summary['stored']} new {$summary['period']} analysis forecasts.");

        return self::SUCCESS;
    }
}
