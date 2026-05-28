<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\RunCryptoForecastCycleRequest;
use App\Models\CryptoAsset;
use Throwable;

class RunCryptoForecastCycleAction
{
    public function __construct(
        private readonly FillMissingCryptoCandlesAction $fillMissing,
        private readonly RunForecastAnalyzersAction $forecasts,
    ) {}

    /**
     * @param  callable(string): void  $stored
     * @param  callable(string): void  $warning
     * @return array{stored:int,period:string}
     */
    public function handle(RunCryptoForecastCycleRequest $request, callable $stored, callable $warning): array
    {
        $settings = $request->settings();
        $assets = CryptoAsset::query()
            ->dashboardList($request->limit)
            ->get();
        $storedCount = 0;

        foreach ($assets as $asset) {
            try {
                $this->fillMissing->handle(
                    [$asset->symbol],
                    [$settings['interval']],
                    $settings['context'],
                );

                $forecasts = $this->forecasts->handle(
                    $asset,
                    $settings['interval'],
                    $settings['horizon'],
                    $settings['context'],
                    $request->freshMinutes,
                );

                if ($forecasts->isNotEmpty()) {
                    $stored("Stored {$forecasts->count()} analysis forecasts for {$asset->symbol}.");
                    $storedCount += $forecasts->count();
                }
            } catch (Throwable $exception) {
                $warning("{$asset->symbol}: {$exception->getMessage()}");
            }
        }

        return [
            'stored' => $storedCount,
            'period' => $request->period,
        ];
    }
}
