<?php

namespace App\Actions\Crypto;

use App\Models\CryptoCandle;
use App\Models\CryptoPredictionStake;
use App\Services\Crypto\CryptoCache;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EvaluatePredictionStakesAction
{
    /**
     * @return array{stakes:int}
     */
    public function handle(int $limit = 100, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        $stakes = CryptoPredictionStake::query()
            ->dueForEvaluation($now)
            ->orderBy('target_at')
            ->limit($limit)
            ->get();

        if ($stakes->isEmpty()) {
            return ['stakes' => 0];
        }

        $candles = CryptoCandle::query()
            ->select(['id', 'crypto_asset_id', 'interval', 'open_time', 'close_time', 'close_price'])
            ->whereIn('crypto_asset_id', $stakes->pluck('crypto_asset_id')->unique()->values())
            ->whereIn('interval', $stakes->pluck('interval')->unique()->values())
            ->where('open_time', '<=', $stakes->max('target_at'))
            ->where('close_time', '>=', $stakes->min('target_at'))
            ->where('close_time', '<=', $now)
            ->orderBy('open_time')
            ->get()
            ->groupBy(fn (CryptoCandle $candle): string => $this->marketKey(
                (int) $candle->crypto_asset_id,
                (string) $candle->interval,
            ));

        $rows = $stakes
            ->map(fn (CryptoPredictionStake $stake): ?array => $this->evaluationRow($stake, $candles, $now))
            ->filter()
            ->values()
            ->all();

        if ($rows === []) {
            return ['stakes' => 0];
        }

        CryptoPredictionStake::query()->upsert(
            $rows,
            ['id'],
            [
                'actual_price',
                'price_delta',
                'absolute_error',
                'absolute_percentage_error',
                'status',
                'direction_correct',
                'resolved_at',
                'updated_at',
            ],
        );

        app(CryptoCache::class)->flush();

        return ['stakes' => count($rows)];
    }

    /**
     * @param  Collection<string, Collection<int, CryptoCandle>>  $candles
     * @return array<string, mixed>|null
     */
    private function evaluationRow(CryptoPredictionStake $stake, Collection $candles, CarbonInterface $now): ?array
    {
        $candle = $candles
            ->get($this->marketKey((int) $stake->crypto_asset_id, (string) $stake->interval), collect())
            ->first(fn (CryptoCandle $candle): bool => $candle->open_time->lessThanOrEqualTo($stake->target_at)
                && $candle->close_time->greaterThanOrEqualTo($stake->target_at));

        if (! $candle) {
            return null;
        }

        $actual = (float) $candle->close_price;
        $target = (float) $stake->target_price;
        $priceDelta = $actual - $target;
        $absoluteError = abs($priceDelta);
        $directionCorrect = $stake->direction === 'above' ? $actual >= $target : $actual <= $target;

        return [
            'id' => $stake->getKey(),
            'crypto_asset_id' => $stake->crypto_asset_id,
            'source' => $stake->source,
            'interval' => $stake->interval,
            'direction' => $stake->direction,
            'target_at' => $stake->target_at,
            'target_price' => $stake->target_price,
            'confidence' => $stake->confidence,
            'entry_price' => $stake->entry_price,
            'actual_price' => (string) $actual,
            'price_delta' => (string) $priceDelta,
            'absolute_error' => (string) $absoluteError,
            'absolute_percentage_error' => $target == 0.0 ? null : (string) (($absoluteError / abs($target)) * 100),
            'status' => $directionCorrect ? 'won' : 'lost',
            'direction_correct' => $directionCorrect,
            'resolved_at' => $now,
            'note' => $stake->note,
            'created_at' => $stake->created_at ?? $now,
            'updated_at' => $now,
        ];
    }

    private function marketKey(int $assetId, string $interval): string
    {
        return "{$assetId}:{$interval}";
    }
}
