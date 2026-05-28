<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\RunCryptoForecastCycleRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use Throwable;

class RunCryptoForecastCycleAction
{
    public function __construct(
        private readonly FillMissingCryptoCandlesAction $fillMissing,
        private readonly RunTimesFmForecastAction $forecasts,
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
            $latest = CryptoForecast::query()
                ->forAsset($asset)
                ->forInterval($settings['interval'])
                ->completed()
                ->where('completed_at', '>=', now()->subMinutes($request->freshMinutes))
                ->orderByDesc('completed_at')
                ->first();

            if ($latest) {
                continue;
            }

            try {
                $this->fillMissing->handle(
                    [$asset->symbol],
                    [$settings['interval']],
                    $settings['context'],
                );

                $forecast = $this->forecasts->handle(
                    $asset,
                    $settings['interval'],
                    $settings['horizon'],
                    $settings['context'],
                );

                $stored("Stored forecast #{$forecast->getKey()} for {$asset->symbol}.");
                $storedCount++;
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
