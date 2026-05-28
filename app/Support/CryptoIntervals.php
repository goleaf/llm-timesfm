<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class CryptoIntervals
{
    public static function seconds(string $interval): int
    {
        return match ($interval) {
            '1s' => 1,
            '1m' => 60,
            '3m' => 180,
            '5m' => 300,
            '15m' => 900,
            '30m' => 1800,
            '1h' => 3600,
            '2h' => 7200,
            '4h' => 14400,
            '6h' => 21600,
            '8h' => 28800,
            '12h' => 43200,
            '1d' => 86400,
            default => throw new InvalidArgumentException("Unsupported crypto interval [{$interval}]."),
        };
    }

    public static function completeOpenTime(string $interval, ?CarbonInterface $now = null): CarbonImmutable
    {
        $now ??= now();
        $seconds = self::seconds($interval);
        $timestamp = $now->getTimestamp();
        $openTimestamp = $timestamp - ($timestamp % $seconds) - $seconds;

        return CarbonImmutable::createFromTimestamp($openTimestamp, 'UTC');
    }

    public static function addSteps(CarbonInterface $time, string $interval, int $steps): CarbonImmutable
    {
        return CarbonImmutable::instance($time)->addSeconds(self::seconds($interval) * $steps);
    }

    public static function milliseconds(CarbonInterface $time): int
    {
        return ($time->getTimestamp() * 1000) + (int) $time->format('v');
    }
}
