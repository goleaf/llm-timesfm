<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
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

        $stored = 0;

        foreach ($rows as $index => $row) {
            if (! is_array($row) || ! isset($row['symbol'], $row['baseAsset'], $row['quoteAsset'])) {
                continue;
            }

            $symbol = strtoupper((string) $row['symbol']);
            $asset = CryptoAsset::query()->firstOrNew(['symbol' => $symbol]);
            $asset->fill([
                'base_asset' => (string) $row['baseAsset'],
                'quote_asset' => (string) $row['quoteAsset'],
                'status' => isset($row['status']) ? (string) $row['status'] : null,
                'base_asset_precision' => isset($row['baseAssetPrecision']) ? (int) $row['baseAssetPrecision'] : null,
                'quote_asset_precision' => isset($row['quoteAssetPrecision']) ? (int) $row['quoteAssetPrecision'] : null,
                'quote_precision' => isset($row['quotePrecision']) ? (int) $row['quotePrecision'] : null,
                'is_spot_trading_allowed' => (bool) ($row['isSpotTradingAllowed'] ?? false),
                'is_margin_trading_allowed' => (bool) ($row['isMarginTradingAllowed'] ?? false),
                'order_types' => $this->arrayValue($row, 'orderTypes'),
                'permissions' => $this->arrayValue($row, 'permissions'),
                'permission_sets' => $this->arrayValue($row, 'permissionSets'),
                'filters' => $this->arrayValue($row, 'filters'),
                'raw_payload' => $row,
                'rank' => $asset->exists ? $asset->rank : $index + 1,
                'is_active' => (string) ($row['status'] ?? '') === 'TRADING',
                'first_seen_at' => $asset->exists ? $asset->first_seen_at : now(),
                'last_seen_at' => now(),
            ]);
            $asset->save();

            $stored++;
        }

        return ['assets' => $stored];
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
