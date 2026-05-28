<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\RunCryptoForecastRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;

class RunConfiguredCryptoForecastAction
{
    public function __construct(
        private readonly RunTimesFmForecastAction $forecasts,
    ) {}

    public function handle(RunCryptoForecastRequest $request): CryptoForecast
    {
        $settings = $request->settings();
        $asset = CryptoAsset::query()
            ->forSymbol($request->symbol)
            ->firstOrFail();

        return $this->forecasts->handle(
            $asset,
            $settings['interval'],
            $settings['horizon'],
            $settings['context'],
        );
    }
}
