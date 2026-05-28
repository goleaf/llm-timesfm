<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\SyncCryptoMetadataRequest;

class SyncConfiguredCryptoMetadataAction
{
    public function __construct(
        private readonly FetchBinanceExchangeInfoAction $metadata,
    ) {}

    /**
     * @return array{assets:int}
     */
    public function handle(SyncCryptoMetadataRequest $request): array
    {
        return $this->metadata->handle($request->symbols());
    }
}
