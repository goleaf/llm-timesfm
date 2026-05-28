<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Services\Crypto\CryptoCache;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FetchBinanceExchangeInfoAction
{
    /**
     * @param  array<int, string>|null  $symbols
     * @return array{assets:int}
     */
    public function handle(?array $symbols = null): array
    {
        $requestedSymbols = array_values(array_filter($symbols ?: config('crypto.binance.symbols', [])));

        if ($requestedSymbols === []) {
            throw new RuntimeException('No Binance symbols configured.');
        }

        $response = $this->client()->get('/api/v3/exchangeInfo', [
            'symbols' => json_encode($requestedSymbols, JSON_THROW_ON_ERROR),
        ])->throw();

        $payload = $response->json();
        $rows = is_array($payload) && isset($payload['symbols']) && is_array($payload['symbols'])
            ? $payload['symbols']
            : [];

        $validRows = collect($rows)
            ->filter(fn ($row): bool => is_array($row) && isset($row['symbol'], $row['baseAsset'], $row['quoteAsset']))
            ->values();

        if ($validRows->isEmpty()) {
            return ['assets' => 0];
        }

        $symbols = $validRows
            ->map(fn (array $row): string => strtoupper((string) $row['symbol']))
            ->unique()
            ->values();
        $existingAssets = CryptoAsset::query()
            ->select(['id', 'symbol', 'rank', 'first_seen_at'])
            ->whereIn('symbol', $symbols)
            ->get()
            ->keyBy('symbol');
        $now = now();
        $assetRows = [];

        foreach ($validRows as $index => $row) {
            $symbol = strtoupper((string) $row['symbol']);
            $existingAsset = $existingAssets->get($symbol);

            $assetRows[] = [
                'symbol' => $symbol,
                'base_asset' => (string) $row['baseAsset'],
                'quote_asset' => (string) $row['quoteAsset'],
                'status' => isset($row['status']) ? (string) $row['status'] : null,
                'base_asset_precision' => isset($row['baseAssetPrecision']) ? (int) $row['baseAssetPrecision'] : null,
                'quote_asset_precision' => isset($row['quoteAssetPrecision']) ? (int) $row['quoteAssetPrecision'] : null,
                'quote_precision' => isset($row['quotePrecision']) ? (int) $row['quotePrecision'] : null,
                'is_spot_trading_allowed' => (bool) ($row['isSpotTradingAllowed'] ?? false),
                'is_margin_trading_allowed' => (bool) ($row['isMarginTradingAllowed'] ?? false),
                'order_types' => json_encode($this->arrayValue($row, 'orderTypes'), JSON_THROW_ON_ERROR),
                'permissions' => json_encode($this->arrayValue($row, 'permissions'), JSON_THROW_ON_ERROR),
                'permission_sets' => json_encode($this->arrayValue($row, 'permissionSets'), JSON_THROW_ON_ERROR),
                'filters' => json_encode($this->arrayValue($row, 'filters'), JSON_THROW_ON_ERROR),
                'raw_payload' => json_encode($row, JSON_THROW_ON_ERROR),
                'rank' => $existingAsset?->rank ?? $index + 1,
                'is_active' => (string) ($row['status'] ?? '') === 'TRADING',
                'first_seen_at' => $existingAsset?->first_seen_at ?? $now,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        CryptoAsset::query()->upsert(
            $assetRows,
            ['symbol'],
            [
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
                'last_seen_at',
                'updated_at',
            ],
        );

        app(CryptoCache::class)->flush();

        return ['assets' => count($assetRows)];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('crypto.binance.base_url'), '/'))
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 250);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int|string, mixed>
     */
    private function arrayValue(array $row, string $key): array
    {
        return isset($row[$key]) && is_array($row[$key]) ? $row[$key] : [];
    }
}
