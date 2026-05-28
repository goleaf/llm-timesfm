<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\MarketsDashboardRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoCandle;

class EnsureMarketHistoryAction
{
    public function __construct(
        private readonly FillMissingCryptoCandlesAction $fillMissing,
    ) {}

    public function handle(MarketsDashboardRequest $request): void
    {
        $asset = CryptoAsset::query()
            ->forSymbol($request->symbol)
            ->withLatestSnapshot()
            ->first();

        if (! $asset) {
            return;
        }

        $exists = CryptoCandle::query()
            ->forAsset($asset)
            ->forInterval($request->interval)
            ->exists();

        if ($exists) {
            return;
        }

        $this->fillMissing->handle(
            [$asset->symbol],
            [$request->interval],
            (int) config('crypto.binance.history_limit'),
        );
    }
}
