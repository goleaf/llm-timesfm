<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoAsset>
 */
class CryptoAssetFactory extends Factory
{
    protected $model = CryptoAsset::class;

    public function definition(): array
    {
        return [
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
            'rank' => 1,
            'is_active' => true,
            'sort_quote_volume' => '1000000.000000000000',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
