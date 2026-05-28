<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoPriceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_asset_id',
        'source',
        'source_event_at',
        'open_time',
        'close_time',
        'price',
        'price_change',
        'price_change_percent',
        'weighted_avg_price',
        'prev_close_price',
        'last_qty',
        'bid_price',
        'bid_qty',
        'ask_price',
        'ask_qty',
        'open_price',
        'high_price',
        'low_price',
        'base_volume',
        'quote_volume',
        'trade_count',
        'first_trade_id',
        'last_trade_id',
        'raw_payload',
    ];

    protected $casts = [
        'source_event_at' => 'immutable_datetime',
        'open_time' => 'immutable_datetime',
        'close_time' => 'immutable_datetime',
        'price' => 'decimal:12',
        'price_change' => 'decimal:12',
        'price_change_percent' => 'decimal:8',
        'weighted_avg_price' => 'decimal:12',
        'prev_close_price' => 'decimal:12',
        'last_qty' => 'decimal:12',
        'bid_price' => 'decimal:12',
        'bid_qty' => 'decimal:12',
        'ask_price' => 'decimal:12',
        'ask_qty' => 'decimal:12',
        'open_price' => 'decimal:12',
        'high_price' => 'decimal:12',
        'low_price' => 'decimal:12',
        'base_volume' => 'decimal:12',
        'quote_volume' => 'decimal:12',
        'trade_count' => 'integer',
        'first_trade_id' => 'integer',
        'last_trade_id' => 'integer',
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

    public function scopeLatestEvents(Builder $query): Builder
    {
        return $this->ensureColumns($query)->orderByDesc('source_event_at');
    }

    private function ensureColumns(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select([
                'id',
                'crypto_asset_id',
                'source',
                'source_event_at',
                'open_time',
                'close_time',
                'price',
                'price_change',
                'price_change_percent',
                'weighted_avg_price',
                'prev_close_price',
                'last_qty',
                'bid_price',
                'bid_qty',
                'ask_price',
                'ask_qty',
                'open_price',
                'high_price',
                'low_price',
                'base_volume',
                'quote_volume',
                'trade_count',
                'first_trade_id',
                'last_trade_id',
                'raw_payload',
                'created_at',
                'updated_at',
            ]);
        }

        return $query;
    }
}
