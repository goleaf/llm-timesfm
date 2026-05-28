<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoCandle>
 */
class CryptoCandleFactory extends Factory
{
    protected $model = CryptoCandle::class;

    private static int $offset = 0;

    public function definition(): array
    {
        $openTime = now()->subMinutes(1000 - self::$offset++)->startOfMinute();

        return [
            'crypto_asset_id' => CryptoAsset::factory(),
            'source' => 'binance',
            'interval' => '1m',
            'open_time' => $openTime,
            'close_time' => $openTime->copy()->addMinute()->subMillisecond(),
            'open_price' => '100.000000000000',
            'high_price' => '105.000000000000',
            'low_price' => '95.000000000000',
            'close_price' => '101.000000000000',
            'base_volume' => '10.000000000000',
            'quote_volume' => '1010.000000000000',
            'trade_count' => 100,
            'raw_payload' => [],
        ];
    }
}
