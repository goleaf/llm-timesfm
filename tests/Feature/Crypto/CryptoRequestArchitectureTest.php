<?php

use App\Http\Requests\Crypto\BackfillCryptoHistoryRequest;
use App\Http\Requests\Crypto\ForecastStatsDashboardRequest;
use App\Http\Requests\Crypto\MarketsDashboardRequest;
use App\Http\Requests\Crypto\RunCryptoForecastCycleRequest;
use App\Http\Requests\Crypto\StorePredictionStakeRequest;
use App\Http\Requests\Crypto\WarmCryptoDashboardCacheRequest;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('normalizes public dashboard input before livewire uses it', function (): void {
    config()->set('app.timezone', 'UTC');
    Carbon::setTestNow(CarbonImmutable::parse('2026-05-28 12:00:00 UTC'));

    $markets = MarketsDashboardRequest::fromRoute('ethusdt');
    $stats = ForecastStatsDashboardRequest::fromRoute('solusdt');
    $stake = StorePredictionStakeRequest::fromState('btcusdt', '1m', '2026-05-28T12:05', '105.50', 'above', 75, null);

    expect($markets->symbol)->toBe('ETHUSDT')
        ->and($markets->interval)->toBe('1m')
        ->and($markets->forecastPeriod)->toBe('1h')
        ->and($markets->withInterval('5m')?->interval)->toBe('5m')
        ->and($markets->withInterval('2h'))->toBeNull()
        ->and($markets->withSymbol('bad-symbol!'))->toBeNull()
        ->and(ForecastStatsDashboardRequest::fromRoute('bad-symbol!')->symbol)->toBe('BTCUSDT')
        ->and($stats->symbol)->toBe('SOLUSDT')
        ->and($stats->withInterval('1d'))->toBeNull()
        ->and($stake->symbol)->toBe('BTCUSDT')
        ->and($stake->targetAt->toDateTimeString())->toBe('2026-05-28 12:05:00');
});

it('validates console command payloads before actions run', function (): void {
    $backfill = BackfillCryptoHistoryRequest::fromConsole('btcusdt', '1m', 240);
    $warm = WarmCryptoDashboardCacheRequest::fromConsole(['btcusdt'], ['5m'], 1);

    expect($backfill->symbol)->toBe('BTCUSDT')
        ->and($backfill->interval)->toBe('1m')
        ->and($backfill->limit)->toBe(240)
        ->and($warm->symbols)->toBe(['BTCUSDT'])
        ->and($warm->intervals)->toBe(['5m'])
        ->and($warm->limit)->toBe(1);

    expect(fn (): RunCryptoForecastCycleRequest => RunCryptoForecastCycleRequest::fromConsole('7d', 3, 5))
        ->toThrow(ValidationException::class);
});
