<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_asset_id',
        'source',
        'interval',
        'context_points',
        'horizon',
        'status',
        'started_at',
        'completed_at',
        'input_starts_at',
        'input_ends_at',
        'target_starts_at',
        'target_ends_at',
        'base_price',
        'total_points',
        'evaluated_points',
        'mean_absolute_error',
        'mean_absolute_percentage_error',
        'direction_accuracy',
        'evaluated_at',
        'point_forecast',
        'quantile_forecast',
        'config',
        'error_message',
    ];

    protected $casts = [
        'context_points' => 'integer',
        'horizon' => 'integer',
        'started_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'input_starts_at' => 'immutable_datetime',
        'input_ends_at' => 'immutable_datetime',
        'target_starts_at' => 'immutable_datetime',
        'target_ends_at' => 'immutable_datetime',
        'base_price' => 'decimal:12',
        'total_points' => 'integer',
        'evaluated_points' => 'integer',
        'mean_absolute_error' => 'decimal:12',
        'mean_absolute_percentage_error' => 'decimal:8',
        'direction_accuracy' => 'decimal:4',
        'evaluated_at' => 'immutable_datetime',
        'point_forecast' => 'array',
        'quantile_forecast' => 'array',
        'config' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CryptoAsset::class, 'crypto_asset_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(CryptoForecastPoint::class);
    }

    public function scopeForAsset(Builder $query, CryptoAsset $asset): Builder
    {
        return $this->ensureColumns($query)->where('crypto_asset_id', $asset->getKey());
    }

    public function scopeForInterval(Builder $query, string $interval): Builder
    {
        return $this->ensureColumns($query)->where('interval', $interval);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $this->ensureColumns($query)->where('status', 'completed');
    }

    public function scopeLatestCompleted(Builder $query): Builder
    {
        return $this->ensureColumns($query)->completed()->orderByDesc('completed_at');
    }

    public function scopeEvaluated(Builder $query): Builder
    {
        return $this->ensureColumns($query)->whereNotNull('evaluated_at');
    }

    private function ensureColumns(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select([
                'id',
                'crypto_asset_id',
                'source',
                'interval',
                'context_points',
                'horizon',
                'status',
                'started_at',
                'completed_at',
                'input_starts_at',
                'input_ends_at',
                'target_starts_at',
                'target_ends_at',
                'base_price',
                'total_points',
                'evaluated_points',
                'mean_absolute_error',
                'mean_absolute_percentage_error',
                'direction_accuracy',
                'evaluated_at',
                'point_forecast',
                'quantile_forecast',
                'config',
                'error_message',
                'created_at',
                'updated_at',
            ]);
        }

        return $query;
    }
}
