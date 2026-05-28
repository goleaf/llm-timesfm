<?php

namespace App\Console\Commands;

use App\Actions\Crypto\RunConfiguredCryptoForecastAction;
use App\Http\Requests\Crypto\RunCryptoForecastRequest;
use Illuminate\Console\Command;

class RunCryptoForecastCommand extends Command
{
    protected $signature = 'crypto:forecast {symbol=BTCUSDT} {period=1h}';

    protected $description = 'Run configured forecast analyzers for a crypto symbol.';

    public function handle(RunConfiguredCryptoForecastAction $forecasts): int
    {
        $request = RunCryptoForecastRequest::fromConsole(
            $this->argument('symbol'),
            $this->argument('period'),
        );
        $stored = $forecasts->handle($request);

        $this->info("Stored {$stored->count()} analysis forecasts for {$request->symbol}.");

        return self::SUCCESS;
    }
}
