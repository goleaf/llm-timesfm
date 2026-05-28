<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoForecastPoint>
 */
class CryptoForecastPointFactory extends Factory
{
    protected $model = CryptoForecastPoint::class;

    public function definition(): array
    {
        $targetOpenTime = now()->startOfMinute();

        return [
            'crypto_forecast_id' => CryptoForecast::factory(),
            'crypto_asset_id' => CryptoAsset::factory(),
            'source' => 'timesfm',
            'interval' => '1m',
            'step' => 1,
            'target_open_time' => $targetOpenTime,
            'base_price' => '100.000000000000',
            'predicted_price' => '101.000000000000',
            'quantile_low' => '100.000000000000',
            'quantile_median' => '101.000000000000',
            'quantile_high' => '102.000000000000',
            'actual_close_price' => null,
            'absolute_error' => null,
            'absolute_percentage_error' => null,
            'direction_correct' => null,
            'evaluated_at' => null,
        ];
    }
}
