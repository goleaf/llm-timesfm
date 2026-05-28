<?php

namespace App\Actions\Crypto;

use App\Actions\Crypto\Concerns\ParsesBinanceTime;
use App\Models\CryptoAsset;
use App\Models\CryptoPriceSnapshot;
use App\Services\Crypto\CryptoCache;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FetchBinanceTickersAction
{
    use ParsesBinanceTime;

    /**
     * @param  array<int, string>|null  $symbols
     * @return array{assets:int,snapshots:int}
     */
    public function handle(?array $symbols = null): array
    {
        $requestedSymbols = array_values(array_filter($symbols ?: config('crypto.binance.symbols', [])));

        if ($requestedSymbols === []) {
            throw new RuntimeException('No Binance symbols configured.');
        }

        $response = $this->client()->get('/api/v3/ticker/24hr', [
            'symbols' => json_encode($requestedSymbols, JSON_THROW_ON_ERROR),
        ])->throw();

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Binance ticker response was not an array.');
        }

        $validRows = collect($payload)
            ->filter(fn ($row): bool => is_array($row) && isset($row['symbol'], $row['closeTime'], $row['lastPrice']))
            ->values();

        if ($validRows->isEmpty()) {
            return [
                'assets' => 0,
                'snapshots' => 0,
            ];
        }

        $symbols = $validRows
            ->map(fn (array $row): string => strtoupper((string) $row['symbol']))
            ->unique()
            ->values();
        $existingAssets = CryptoAsset::query()
            ->select(['id', 'symbol', 'first_seen_at', 'created_at'])
            ->whereIn('symbol', $symbols)
            ->get()
            ->keyBy('symbol');
        $now = now();
        $assetRows = [];

        foreach ($validRows as $index => $row) {
            $symbol = strtoupper((string) $row['symbol']);
            [$baseAsset, $quoteAsset] = $this->splitSymbol($symbol);
            $eventTime = $this->fromBinanceMilliseconds((int) $row['closeTime']);
            $existingAsset = $existingAssets->get($symbol);

            $assetRows[] = [
                'symbol' => $symbol,
                'base_asset' => $baseAsset,
                'quote_asset' => $quoteAsset,
                'rank' => $index + 1,
                'is_active' => true,
                'sort_quote_volume' => (string) ($row['quoteVolume'] ?? '0'),
                'first_seen_at' => $existingAsset?->first_seen_at ?? $now,
                'last_seen_at' => $eventTime,
                'created_at' => $existingAsset?->created_at ?? $now,
                'updated_at' => $now,
            ];
        }

        CryptoAsset::query()->upsert(
            $assetRows,
            ['symbol'],
            [
                'base_asset',
                'quote_asset',
                'rank',
                'is_active',
                'sort_quote_volume',
                'last_seen_at',
                'updated_at',
            ],
        );

        $assets = CryptoAsset::query()
            ->select(['id', 'symbol'])
            ->whereIn('symbol', $symbols)
            ->get()
            ->keyBy('symbol');
        $snapshotRows = [];

        foreach ($validRows as $row) {
            $symbol = strtoupper((string) $row['symbol']);
            $asset = $assets->get($symbol);

            if (! $asset) {
                continue;
            }

            $eventTime = $this->fromBinanceMilliseconds((int) $row['closeTime']);
            $snapshotRows[] = [
                'crypto_asset_id' => $asset->getKey(),
                'source' => 'binance',
                'source_event_at' => $eventTime,
                'open_time' => isset($row['openTime']) ? $this->fromBinanceMilliseconds((int) $row['openTime']) : null,
                'close_time' => $eventTime,
                'price' => (string) $row['lastPrice'],
                'price_change' => $this->nullableString($row, 'priceChange'),
                'price_change_percent' => $this->nullableString($row, 'priceChangePercent'),
                'weighted_avg_price' => $this->nullableString($row, 'weightedAvgPrice'),
                'prev_close_price' => $this->nullableString($row, 'prevClosePrice'),
                'last_qty' => $this->nullableString($row, 'lastQty'),
                'bid_price' => $this->nullableString($row, 'bidPrice'),
                'bid_qty' => $this->nullableString($row, 'bidQty'),
                'ask_price' => $this->nullableString($row, 'askPrice'),
                'ask_qty' => $this->nullableString($row, 'askQty'),
                'open_price' => (string) ($row['openPrice'] ?? $row['lastPrice']),
                'high_price' => (string) ($row['highPrice'] ?? $row['lastPrice']),
                'low_price' => (string) ($row['lowPrice'] ?? $row['lastPrice']),
                'base_volume' => (string) ($row['volume'] ?? '0'),
                'quote_volume' => (string) ($row['quoteVolume'] ?? '0'),
                'trade_count' => (int) ($row['count'] ?? 0),
                'first_trade_id' => isset($row['firstId']) ? (int) $row['firstId'] : null,
                'last_trade_id' => isset($row['lastId']) ? (int) $row['lastId'] : null,
                'raw_payload' => json_encode($row, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($snapshotRows !== []) {
            CryptoPriceSnapshot::query()->upsert(
                $snapshotRows,
                ['crypto_asset_id', 'source', 'source_event_at'],
                [
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
                    'updated_at',
                ],
            );
        }

        app(CryptoCache::class)->flush();

        return [
            'assets' => count($assetRows),
            'snapshots' => count($snapshotRows),
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('crypto.binance.base_url'), '/'))
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitSymbol(string $symbol): array
    {
        foreach (config('crypto.binance.quote_assets', ['USDT']) as $quoteAsset) {
            if (str_ends_with($symbol, $quoteAsset)) {
                return [substr($symbol, 0, -strlen($quoteAsset)), $quoteAsset];
            }
        }

        return [$symbol, ''];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function nullableString(array $row, string $key): ?string
    {
        if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        return (string) $row[$key];
    }
}
