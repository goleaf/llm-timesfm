<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\SyncCryptoTickersRequest;

class SyncConfiguredCryptoTickersAction
{
    public function __construct(
        private readonly FetchBinanceTickersAction $tickers,
        private readonly WarmCryptoDashboardCacheAction $cache,
    ) {}

    /**
     * @return array{snapshots:int,warmed_reads:int}
     */
    public function handle(SyncCryptoTickersRequest $request): array
    {
        $symbols = $request->symbols();
        $summary = $this->tickers->handle($symbols);
        $warmed = ['reads' => 0];

        if ((bool) config('crypto.cache.warm_after_ticker_sync', true)) {
            $warmed = $this->cache->handle($symbols, null, (int) config('crypto.cache.warm_limit', 3));
        }

        return [
            'snapshots' => $summary['snapshots'],
            'warmed_reads' => $warmed['reads'],
        ];
    }
}
