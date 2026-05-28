<?php

namespace App\Console\Commands;

use App\Actions\Crypto\RunConfiguredCryptoForecastAction;
use App\Http\Requests\Crypto\RunCryptoForecastRequest;
use Illuminate\Console\Command;

class RunCryptoForecastCommand extends Command
{
    protected $signature = 'crypto:forecast {symbol=BTCUSDT} {period=1h}';

    protected $description = 'Run a forecast for a crypto symbol using TimesFM when enabled.';

    public function handle(RunConfiguredCryptoForecastAction $forecasts): int
    {
        $request = RunCryptoForecastRequest::fromConsole(
            $this->argument('symbol'),
            $this->argument('period'),
        );
        $forecast = $forecasts->handle($request);

        $this->info("Stored {$forecast->source} forecast #{$forecast->getKey()} for {$request->symbol}.");

        return self::SUCCESS;
    }
}
