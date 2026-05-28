<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoCandle extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_asset_id',
        'source',
        'interval',
        'open_time',
        'close_time',
        'open_price',
        'high_price',
        'low_price',
        'close_price',
        'base_volume',
        'quote_volume',
        'trade_count',
        'taker_buy_base_volume',
        'taker_buy_quote_volume',
        'ignored_value',
        'raw_payload',
    ];

    protected $casts = [
        'open_time' => 'immutable_datetime',
        'close_time' => 'immutable_datetime',
        'open_price' => 'decimal:12',
        'high_price' => 'decimal:12',
        'low_price' => 'decimal:12',
        'close_price' => 'decimal:12',
        'base_volume' => 'decimal:12',
        'quote_volume' => 'decimal:12',
        'trade_count' => 'integer',
        'taker_buy_base_volume' => 'decimal:12',
        'taker_buy_quote_volume' => 'decimal:12',
        'raw_payload' => 'array',
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

    public function scopeLatestComplete(Builder $query): Builder
    {
        return $this->ensureColumns($query)->orderByDesc('open_time');
    }

    private function ensureColumns(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select([
                'id',
                'crypto_asset_id',
                'source',
                'interval',
                'open_time',
                'close_time',
                'open_price',
                'high_price',
                'low_price',
                'close_price',
                'base_volume',
                'quote_volume',
                'trade_count',
                'taker_buy_base_volume',
                'taker_buy_quote_volume',
                'ignored_value',
                'raw_payload',
                'created_at',
                'updated_at',
            ]);
        }

        return $query;
    }
}
