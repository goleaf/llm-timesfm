<?php

namespace App\Actions\Crypto\Concerns;

use Carbon\CarbonImmutable;

trait ParsesBinanceTime
{
    private function fromBinanceMilliseconds(int $milliseconds): CarbonImmutable
    {
        $seconds = intdiv($milliseconds, 1000);
        $remainingMilliseconds = $milliseconds % 1000;

        return CarbonImmutable::createFromTimestamp($seconds, 'UTC')
            ->addMilliseconds($remainingMilliseconds);
    }
}
