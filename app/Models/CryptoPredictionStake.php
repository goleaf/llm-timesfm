<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoPredictionStake extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_asset_id',
        'source',
        'interval',
        'direction',
        'target_at',
        'target_price',
        'confidence',
        'entry_price',
        'actual_price',
        'price_delta',
        'absolute_error',
        'absolute_percentage_error',
        'status',
        'direction_correct',
        'resolved_at',
        'note',
    ];

    protected $casts = [
        'target_at' => 'immutable_datetime',
        'target_price' => 'decimal:12',
        'confidence' => 'integer',
        'entry_price' => 'decimal:12',
        'actual_price' => 'decimal:12',
        'price_delta' => 'decimal:12',
        'absolute_error' => 'decimal:12',
        'absolute_percentage_error' => 'decimal:8',
        'direction_correct' => 'boolean',
        'resolved_at' => 'immutable_datetime',
    ];

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

    public function scopePending(Builder $query): Builder
    {
        return $this->ensureColumns($query)->where('status', 'pending');
    }

    public function scopeDueForEvaluation(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();

        return $this->ensureColumns($query)
            ->pending()
            ->where('target_at', '<=', $now);
    }

    public function scopeDashboardList(Builder $query, CryptoAsset $asset, string $interval, int $limit = 12): Builder
    {
        return $this->ensureColumns($query)
            ->forAsset($asset)
            ->forInterval($interval)
            ->orderByDesc('target_at')
            ->orderByDesc('id')
            ->limit($limit);
    }

    private function ensureColumns(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select([
                'id',
                'crypto_asset_id',
                'source',
                'interval',
                'direction',
                'target_at',
                'target_price',
                'confidence',
                'entry_price',
                'actual_price',
                'price_delta',
                'absolute_error',
                'absolute_percentage_error',
                'status',
                'direction_correct',
                'resolved_at',
                'note',
                'created_at',
                'updated_at',
            ]);
        }

        return $query;
    }
}
