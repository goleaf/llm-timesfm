<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\StorePredictionStakeRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoPredictionStake;
use Illuminate\Validation\ValidationException;

class CreatePredictionStakeAction
{
    public function handle(StorePredictionStakeRequest $request): CryptoPredictionStake
    {
        $asset = CryptoAsset::query()
            ->forSymbol($request->symbol)
            ->withLatestSnapshot()
            ->first();

        if (! $asset) {
            throw ValidationException::withMessages([
                'symbol' => __('ui.messages.selected_market_unavailable'),
            ]);
        }

        return CryptoPredictionStake::query()->create([
            'crypto_asset_id' => $asset->getKey(),
            'source' => 'manual',
            'interval' => $request->interval,
            'direction' => $request->direction,
            'target_at' => $request->targetAt,
            'target_price' => $request->targetPrice,
            'confidence' => $request->confidence,
            'entry_price' => $asset->latestSnapshot?->price,
            'status' => 'pending',
            'note' => $request->note,
        ]);
    }
}
