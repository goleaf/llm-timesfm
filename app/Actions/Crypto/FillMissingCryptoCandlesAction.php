<?php

namespace App\Actions\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Support\CryptoIntervals;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class FillMissingCryptoCandlesAction
{
    public function __construct(
        private readonly FetchBinanceKlinesAction $klines,
    ) {}

    /**
     * @param  array<int, string>|null  $symbols
     * @param  array<int, string>|null  $intervals
     * @return array{assets:int,intervals:int,requests:int,candles:int}
     */
    public function handle(?array $symbols = null, ?array $intervals = null, int $recentWindow = 720): array
    {
        $intervals = array_values(array_filter($intervals ?: config('crypto.binance.short_intervals', ['1m', '5m'])));
        $assets = $this->assets($symbols);
        $requests = 0;
        $candles = 0;

        foreach ($assets as $asset) {
            foreach ($intervals as $interval) {
                $summary = $this->fillAssetInterval($asset, $interval, $recentWindow);
                $requests += $summary['requests'];
                $candles += $summary['candles'];
            }
        }

        return [
            'assets' => $assets->count(),
            'intervals' => count($intervals),
            'requests' => $requests,
            'candles' => $candles,
        ];
    }

    /**
     * @return Collection<int, CryptoAsset>
     */
    private function assets(?array $symbols): Collection
    {
        $query = CryptoAsset::query()
            ->active()
            ->orderBy('rank')
            ->orderByDesc('sort_quote_volume')
            ->limit((int) config('crypto.binance.market_limit', 20));

        if ($symbols !== null && $symbols !== []) {
            $query->whereIn('symbol', array_map('strtoupper', $symbols));
        }

        return $query->get();
    }

    /**
     * @return array{requests:int,candles:int}
     */
    private function fillAssetInterval(CryptoAsset $asset, string $interval, int $recentWindow): array
    {
        $currentCompleteOpen = CryptoIntervals::completeOpenTime($interval);
        $startTime = $this->nextMissingOpenTime($asset, $interval, $currentCompleteOpen, $recentWindow);

        if ($startTime === null) {
            return [
                'requests' => 1,
                'candles' => $this->klines->handle(
                    $asset,
                    $interval,
                    min($recentWindow, (int) config('crypto.binance.max_kline_limit', 1000)),
                ),
            ];
        }

        if ($startTime->greaterThan($currentCompleteOpen)) {
            return ['requests' => 0, 'candles' => 0];
        }

        $requests = 0;
        $candles = 0;
        $chunkLimit = (int) config('crypto.binance.max_kline_limit', 1000);
        $safety = 0;

        while ($startTime->lessThanOrEqualTo($currentCompleteOpen) && $safety < 100) {
            $chunkEnd = CryptoIntervals::addSteps($startTime, $interval, $chunkLimit - 1);

            if ($chunkEnd->greaterThan($currentCompleteOpen)) {
                $chunkEnd = $currentCompleteOpen;
            }

            $stored = $this->klines->handle($asset, $interval, $chunkLimit, $startTime, $chunkEnd);
            $requests++;
            $candles += $stored;

            if ($stored === 0) {
                break;
            }

            $startTime = CryptoIntervals::addSteps($chunkEnd, $interval, 1);
            $safety++;
        }

        return ['requests' => $requests, 'candles' => $candles];
    }

    private function nextMissingOpenTime(
        CryptoAsset $asset,
        string $interval,
        CarbonImmutable $currentCompleteOpen,
        int $recentWindow,
    ): ?CarbonImmutable {
        $latest = CryptoCandle::query()
            ->select(['id', 'crypto_asset_id', 'interval', 'open_time'])
            ->where('crypto_asset_id', $asset->getKey())
            ->where('interval', $interval)
            ->orderByDesc('open_time')
            ->first();

        if (! $latest) {
            return null;
        }

        $windowStart = CryptoIntervals::addSteps($currentCompleteOpen, $interval, -max($recentWindow - 1, 1));
        $recentOpenTimes = CryptoCandle::query()
            ->select(['id', 'crypto_asset_id', 'interval', 'open_time'])
            ->where('crypto_asset_id', $asset->getKey())
            ->where('interval', $interval)
            ->where('open_time', '>=', $windowStart)
            ->where('open_time', '<=', $currentCompleteOpen)
            ->orderBy('open_time')
            ->limit($recentWindow + 5)
            ->get()
            ->mapWithKeys(fn (CryptoCandle $candle): array => [$candle->open_time->getTimestamp() => true]);

        $seconds = CryptoIntervals::seconds($interval);
        $cursor = $windowStart;

        while ($cursor->lessThanOrEqualTo($currentCompleteOpen)) {
            if (! $recentOpenTimes->has($cursor->getTimestamp())) {
                return $cursor;
            }

            $cursor = $cursor->addSeconds($seconds);
        }

        return CryptoIntervals::addSteps($latest->open_time, $interval, 1);
    }
}
