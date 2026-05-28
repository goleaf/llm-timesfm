<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\MarketsDashboardRequest;
use App\Models\CryptoAsset;
use RuntimeException;

class LoadMarketHistoryAction
{
    public function __construct(
        private readonly FillMissingCryptoCandlesAction $fillMissing,
    ) {}

    public function handle(MarketsDashboardRequest $request): string
    {
        $asset = CryptoAsset::query()
            ->forSymbol($request->symbol)
            ->withLatestSnapshot()
            ->first();

        if (! $asset) {
            throw new RuntimeException(__('ui.messages.market_not_loaded'));
        }

        $this->fillMissing->handle(
            [$asset->symbol],
            [$request->interval],
            (int) config('crypto.binance.history_limit'),
        );

        return __('ui.messages.history_loaded', ['symbol' => $asset->symbol]);
    }
}
