<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\BackfillCryptoHistoryRequest;
use App\Models\CryptoAsset;

class BackfillCryptoHistoryAction
{
    public function __construct(
        private readonly FetchBinanceKlinesAction $klines,
    ) {}

    /**
     * @return array<int, array{symbol:string,interval:string,stored:int}>
     */
    public function handle(BackfillCryptoHistoryRequest $request): array
    {
        $assets = $request->symbol
            ? CryptoAsset::query()->forSymbol($request->symbol)->limit(1)->get()
            : CryptoAsset::query()->dashboardList(20)->get();

        return $assets
            ->map(fn (CryptoAsset $asset): array => [
                'symbol' => $asset->symbol,
                'interval' => $request->interval,
                'stored' => $this->klines->handle($asset, $request->interval, $request->limit),
            ])
            ->values()
            ->all();
    }
}
