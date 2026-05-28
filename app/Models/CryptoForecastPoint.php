<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoForecastPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_forecast_id',
        'crypto_asset_id',
        'source',
        'interval',
        'step',
        'target_open_time',
        'base_price',
        'predicted_price',
        'quantile_low',
        'quantile_median',
        'quantile_high',
        'actual_close_price',
        'absolute_error',
        'absolute_percentage_error',
        'direction_correct',
        'evaluated_at',
    ];

    protected $casts = [
        'step' => 'integer',
        'target_open_time' => 'immutable_datetime',
        'base_price' => 'decimal:12',
        'predicted_price' => 'decimal:12',
        'quantile_low' => 'decimal:12',
        'quantile_median' => 'decimal:12',
        'quantile_high' => 'decimal:12',
        'actual_close_price' => 'decimal:12',
        'absolute_error' => 'decimal:12',
        'absolute_percentage_error' => 'decimal:8',
        'direction_correct' => 'boolean',
        'evaluated_at' => 'immutable_datetime',
    ];

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(CryptoForecast::class, 'crypto_forecast_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'crypto_asset_id');
    }

    public function scopeForAsset(Builder $query, CryptoAsset $asset): Builder
    {
        return $this->ensureColumns($query)->where('crypto_asset_id', $asset->getKey());
    }

    public function scopeForInterval(Builder $query, string $interval): Builder
    {
        return $this->ensureColumns($query)->where('interval', $interval);
    }

    public function scopePendingEvaluation(Builder $query): Builder
    {
        return $this->ensureColumns($query)->whereNull('evaluated_at');
    }

    public function scopeEvaluated(Builder $query): Builder
    {
        return $this->ensureColumns($query)->whereNotNull('evaluated_at');
    }

    public function scopeDueForEvaluation(Builder $query): Builder
    {
        return $this->ensureColumns($query)
            ->whereNull('evaluated_at')
            ->where('target_open_time', '<=', now());
    }

    private function ensureColumns(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select([
                'id',
                'crypto_forecast_id',
                'crypto_asset_id',
                'source',
                'interval',
                'step',
                'target_open_time',
                'base_price',
                'predicted_price',
                'quantile_low',
                'quantile_median',
                'quantile_high',
                'actual_close_price',
                'absolute_error',
                'absolute_percentage_error',
                'direction_correct',
                'evaluated_at',
                'created_at',
                'updated_at',
            ]);
        }

        return $query;
    }
}
