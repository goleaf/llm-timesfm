<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\MarketsDashboardRequest;
use App\Models\CryptoAsset;
use RuntimeException;

class RunMarketForecastAction
{
    public function __construct(
        private readonly FillMissingCryptoCandlesAction $fillMissing,
        private readonly RunForecastAnalyzersAction $forecasts,
    ) {}

    /**
     * @return array{interval:string,message:string}
     */
    public function handle(MarketsDashboardRequest $request): array
    {
        $settings = config("crypto.forecasting.periods.{$request->forecastPeriod}");
        $asset = CryptoAsset::query()
            ->forSymbol($request->symbol)
            ->withLatestSnapshot()
            ->first();

        if (! $asset || ! is_array($settings)) {
            throw new RuntimeException(__('ui.messages.forecast_unavailable'));
        }

        $interval = (string) $settings['interval'];
        $context = (int) $settings['context'];
        $this->fillMissing->handle([$asset->symbol], [$interval], $context);

        $forecasts = $this->forecasts->handle(
            $asset,
            $interval,
            (int) $settings['horizon'],
            $context,
        );

        return [
            'interval' => $interval,
            'message' => __('ui.messages.stored_forecasts', ['count' => $forecasts->count()]),
        ];
    }
}
