<?php

namespace App\Actions\Crypto;

use App\Actions\Crypto\Concerns\ParsesBinanceTime;
use App\Models\CryptoAsset;
use App\Models\CryptoPriceSnapshot;
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

        $snapshots = 0;

        foreach ($payload as $index => $row) {
            if (! is_array($row) || ! isset($row['symbol'], $row['closeTime'], $row['lastPrice'])) {
                continue;
            }

            $symbol = strtoupper((string) $row['symbol']);
            [$baseAsset, $quoteAsset] = $this->splitSymbol($symbol);
            $eventTime = $this->fromBinanceMilliseconds((int) $row['closeTime']);

            $asset = CryptoAsset::query()->firstOrNew(['symbol' => $symbol]);
            $asset->fill([
                'base_asset' => $baseAsset,
                'quote_asset' => $quoteAsset,
                'rank' => $index + 1,
                'is_active' => true,
                'sort_quote_volume' => (string) ($row['quoteVolume'] ?? '0'),
                'first_seen_at' => $asset->exists ? $asset->first_seen_at : now(),
                'last_seen_at' => $eventTime,
            ]);
            $asset->save();

            CryptoPriceSnapshot::query()->updateOrCreate(
                [
                    'crypto_asset_id' => $asset->getKey(),
                    'source' => 'binance',
                    'source_event_at' => $eventTime,
                ],
                [
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
                    'raw_payload' => $row,
                ],
            );

            $snapshots++;
        }

        return [
            'assets' => count($payload),
            'snapshots' => $snapshots,
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
