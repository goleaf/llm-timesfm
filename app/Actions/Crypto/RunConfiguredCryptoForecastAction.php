<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\RunCryptoForecastRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use Illuminate\Support\Collection;

class RunConfiguredCryptoForecastAction
{
    public function __construct(
        private readonly RunForecastAnalyzersAction $forecasts,
    ) {}

    /**
     * @return Collection<int, CryptoForecast>
     */
    public function handle(RunCryptoForecastRequest $request): Collection
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
