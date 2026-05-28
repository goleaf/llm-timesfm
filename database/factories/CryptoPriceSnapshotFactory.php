<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use App\Models\CryptoPriceSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoPriceSnapshot>
 */
class CryptoPriceSnapshotFactory extends Factory
{
    protected $model = CryptoPriceSnapshot::class;

    public function definition(): array
    {
        return [
            'crypto_asset_id' => CryptoAsset::factory(),
            'source' => 'binance',
            'source_event_at' => now(),
            'price' => '100.000000000000',
            'open_price' => '95.000000000000',
            'high_price' => '105.000000000000',
            'low_price' => '90.000000000000',
            'base_volume' => '10.000000000000',
            'quote_volume' => '1000.000000000000',
            'trade_count' => 100,
            'raw_payload' => ['symbol' => 'BTCUSDT'],
        ];
    }
}
