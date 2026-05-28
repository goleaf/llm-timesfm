<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoForecast>
 */
class CryptoForecastFactory extends Factory
{
    protected $model = CryptoForecast::class;

    public function definition(): array
    {
        $completedAt = now()->startOfMinute();

        return [
            'crypto_asset_id' => CryptoAsset::factory(),
            'source' => 'timesfm',
            'interval' => '1m',
            'context_points' => 120,
            'horizon' => 3,
            'status' => 'completed',
            'started_at' => $completedAt->copy()->subSeconds(10),
            'completed_at' => $completedAt,
            'input_starts_at' => $completedAt->copy()->subMinutes(120),
            'input_ends_at' => $completedAt->copy()->subMinute(),
            'target_starts_at' => $completedAt,
            'target_ends_at' => $completedAt->copy()->addMinutes(2),
            'base_price' => '100.000000000000',
            'total_points' => 3,
            'evaluated_points' => 0,
            'point_forecast' => [101.0, 102.0, 103.0],
            'quantile_forecast' => [
                [100.0, 101.0, 102.0],
                [101.0, 102.0, 103.0],
                [102.0, 103.0, 104.0],
            ],
            'config' => ['engine' => 'timesfm'],
        ];
    }
}
