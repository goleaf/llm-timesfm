<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use App\Models\CryptoPredictionStake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoPredictionStake>
 */
class CryptoPredictionStakeFactory extends Factory
{
    protected $model = CryptoPredictionStake::class;

    public function definition(): array
    {
        return [
            'crypto_asset_id' => CryptoAsset::factory(),
            'source' => 'manual',
            'interval' => '1m',
            'direction' => 'above',
            'target_at' => now()->addHour()->startOfMinute(),
            'target_price' => '105.000000000000',
            'confidence' => 60,
            'entry_price' => '100.000000000000',
            'status' => 'pending',
            'note' => null,
        ];
    }
}
