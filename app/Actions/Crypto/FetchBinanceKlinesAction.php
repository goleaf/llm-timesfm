<?php

namespace App\Actions\Crypto;

use App\Actions\Crypto\Concerns\ParsesBinanceTime;
use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Support\CryptoIntervals;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FetchBinanceKlinesAction
{
    use ParsesBinanceTime;

    public function handle(
        CryptoAsset $asset,
        string $interval = '1m',
        int $limit = 240,
        ?CarbonInterface $startTime = null,
        ?CarbonInterface $endTime = null,
    ): int {
        $query = [
            'symbol' => $asset->symbol,
            'interval' => $interval,
            'limit' => max(1, min($limit, 1000)),
        ];

        if ($startTime) {
            $query['startTime'] = CryptoIntervals::milliseconds($startTime);
        }

        if ($endTime) {
            $query['endTime'] = CryptoIntervals::milliseconds($endTime);
        }

        $response = $this->client()->get('/api/v3/klines', $query)->throw();

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Binance kline response was not an array.');
        }

        $stored = 0;

        foreach ($payload as $row) {
            if (! is_array($row) || count($row) < 11) {
                continue;
            }

            $openTime = $this->fromBinanceMilliseconds((int) $row[0]);

            CryptoCandle::query()->updateOrCreate(
                [
                    'crypto_asset_id' => $asset->getKey(),
                    'source' => 'binance',
                    'interval' => $interval,
                    'open_time' => $openTime,
                ],
                [
                    'close_time' => $this->fromBinanceMilliseconds((int) $row[6]),
                    'open_price' => (string) $row[1],
                    'high_price' => (string) $row[2],
                    'low_price' => (string) $row[3],
                    'close_price' => (string) $row[4],
                    'base_volume' => (string) $row[5],
                    'quote_volume' => (string) $row[7],
                    'trade_count' => (int) $row[8],
                    'taker_buy_base_volume' => (string) ($row[9] ?? '0'),
                    'taker_buy_quote_volume' => (string) ($row[10] ?? '0'),
                    'ignored_value' => isset($row[11]) ? (string) $row[11] : null,
                    'raw_payload' => $row,
                ],
            );

            $stored++;
        }

        return $stored;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('crypto.binance.base_url'), '/'))
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 250);
    }
}
