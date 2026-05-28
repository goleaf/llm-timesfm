<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'base_asset',
        'quote_asset',
        'status',
        'base_asset_precision',
        'quote_asset_precision',
        'quote_precision',
        'is_spot_trading_allowed',
        'is_margin_trading_allowed',
        'order_types',
        'permissions',
        'permission_sets',
        'filters',
        'raw_payload',
        'rank',
        'is_active',
        'sort_quote_volume',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'rank' => 'integer',
        'is_active' => 'boolean',
        'base_asset_precision' => 'integer',
        'quote_asset_precision' => 'integer',
        'quote_precision' => 'integer',
        'is_spot_trading_allowed' => 'boolean',
        'is_margin_trading_allowed' => 'boolean',
        'order_types' => 'array',
        'permissions' => 'array',
        'permission_sets' => 'array',
        'filters' => 'array',
        'raw_payload' => 'array',
        'sort_quote_volume' => 'decimal:12',
        'first_seen_at' => 'immutable_datetime',
        'last_seen_at' => 'immutable_datetime',
    ];

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(CryptoPriceSnapshot::class)->latestOfMany('source_event_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(CryptoPriceSnapshot::class);
    }

    public function candles(): HasMany
    {
        return $this->hasMany(CryptoCandle::class);
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(CryptoForecast::class);
    }

    public function forecastPoints(): HasMany
    {
        return $this->hasMany(CryptoForecastPoint::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $this->ensureColumns($query)->where('is_active', true);
    }

    public function scopeForSymbol(Builder $query, string $symbol): Builder
    {
        return $this->ensureColumns($query)->where('symbol', strtoupper($symbol));
    }

    public function scopeDashboardList(Builder $query, int $limit = 20): Builder
    {
        return $this->ensureColumns($query)
            ->active()
            ->withLatestSnapshot()
            ->orderBy('rank')
            ->orderByDesc('sort_quote_volume')
            ->limit($limit);
    }

    public function scopeWithLatestSnapshot(Builder $query): Builder
    {
        return $query->with([
            'latestSnapshot' => fn ($snapshotQuery) => $snapshotQuery->select([
                'crypto_price_snapshots.id',
                'crypto_price_snapshots.crypto_asset_id',
                'crypto_price_snapshots.source',
                'crypto_price_snapshots.source_event_at',
                'crypto_price_snapshots.open_time',
                'crypto_price_snapshots.close_time',
                'crypto_price_snapshots.price',
                'crypto_price_snapshots.price_change',
                'crypto_price_snapshots.price_change_percent',
                'crypto_price_snapshots.weighted_avg_price',
                'crypto_price_snapshots.prev_close_price',
                'crypto_price_snapshots.last_qty',
                'crypto_price_snapshots.bid_price',
                'crypto_price_snapshots.bid_qty',
                'crypto_price_snapshots.ask_price',
                'crypto_price_snapshots.ask_qty',
                'crypto_price_snapshots.open_price',
                'crypto_price_snapshots.high_price',
                'crypto_price_snapshots.low_price',
                'crypto_price_snapshots.base_volume',
                'crypto_price_snapshots.quote_volume',
                'crypto_price_snapshots.trade_count',
                'crypto_price_snapshots.first_trade_id',
                'crypto_price_snapshots.last_trade_id',
                'crypto_price_snapshots.raw_payload',
                'crypto_price_snapshots.created_at',
                'crypto_price_snapshots.updated_at',
            ]),
        ]);
    }

    protected function displayPair(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->base_asset}/{$this->quote_asset}");
    }

    private function ensureColumns(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select([
                'id',
                'symbol',
                'base_asset',
                'quote_asset',
                'status',
                'base_asset_precision',
                'quote_asset_precision',
                'quote_precision',
                'is_spot_trading_allowed',
                'is_margin_trading_allowed',
                'order_types',
                'permissions',
                'permission_sets',
                'filters',
                'raw_payload',
                'rank',
                'is_active',
                'sort_quote_volume',
                'first_seen_at',
                'last_seen_at',
                'created_at',
                'updated_at',
            ]);
        }

        return $query;
    }
}
