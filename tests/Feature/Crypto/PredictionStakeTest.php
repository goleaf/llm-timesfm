<?php

use App\Actions\Crypto\CreatePredictionStakeAction;
use App\Actions\Crypto\EvaluatePredictionStakesAction;
use App\Http\Requests\Crypto\StorePredictionStakeRequest;
use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('validates a manual prediction stake for a specific target time', function (): void {
    config()->set('app.timezone', 'UTC');
    Carbon::setTestNow(CarbonImmutable::parse('2026-05-28 12:00:00 UTC'));

    $request = StorePredictionStakeRequest::fromState(
        'btcusdt',
        '1m',
        '2026-05-28T12:05',
        '105.50',
        'above',
        75,
        'breakout',
    );

    expect($request->symbol)->toBe('BTCUSDT')
        ->and($request->interval)->toBe('1m')
        ->and($request->targetAt->toDateTimeString())->toBe('2026-05-28 12:05:00')
        ->and($request->targetPrice)->toBe('105.50')
        ->and($request->direction)->toBe('above')
        ->and($request->confidence)->toBe(75)
        ->and($request->note)->toBe('breakout');

    expect(fn (): StorePredictionStakeRequest => StorePredictionStakeRequest::fromState(
        'BTCUSDT',
        '1m',
        '2026-05-28T12:05',
        '105.50',
        'sideways',
        75,
        null,
    ))->toThrow(ValidationException::class);
});

it('creates and evaluates a prediction stake against the matching stored candle', function (): void {
    config()->set('app.timezone', 'UTC');
    Carbon::setTestNow(CarbonImmutable::parse('2026-05-28 12:00:00 UTC'));

    $asset = CryptoAsset::factory()
        ->hasSnapshots(1, [
            'source_event_at' => CarbonImmutable::parse('2026-05-28 12:00:00 UTC'),
            'price' => '100.000000000000',
        ])
        ->create([
            'symbol' => 'BTCUSDT',
            'base_asset' => 'BTC',
            'quote_asset' => 'USDT',
        ]);

    $stake = app(CreatePredictionStakeAction::class)->handle(
        StorePredictionStakeRequest::fromState(
            'BTCUSDT',
            '1m',
            '2026-05-28T12:05',
            '105.00000000',
            'above',
            80,
            'breakout',
        ),
    );

    expect($stake->crypto_asset_id)->toBe($asset->getKey())
        ->and($stake->entry_price)->toEqual('100.000000000000')
        ->and($stake->status)->toBe('pending');

    Carbon::setTestNow(CarbonImmutable::parse('2026-05-28 12:06:00 UTC'));

    CryptoCandle::factory()->create([
        'crypto_asset_id' => $asset->getKey(),
        'interval' => '1m',
        'open_time' => CarbonImmutable::parse('2026-05-28 12:05:00 UTC'),
        'close_time' => CarbonImmutable::parse('2026-05-28 12:05:59 UTC'),
        'close_price' => '110.000000000000',
    ]);

    $summary = app(EvaluatePredictionStakesAction::class)->handle(100);
    $stake->refresh();

    expect($summary)->toBe(['stakes' => 1])
        ->and($stake->status)->toBe('won')
        ->and($stake->direction_correct)->toBeTrue()
        ->and($stake->actual_price)->toEqual('110.000000000000')
        ->and($stake->absolute_error)->toEqual('5.000000000000')
        ->and($stake->resolved_at?->toDateTimeString())->toBe('2026-05-28 12:06:00');
});
