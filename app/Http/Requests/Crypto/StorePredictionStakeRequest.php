<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;
use App\Support\CryptoIntervals;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

final readonly class StorePredictionStakeRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public string $symbol,
        public string $interval,
        public CarbonImmutable $targetAt,
        public string $targetPrice,
        public string $direction,
        public int $confidence,
        public ?string $note,
    ) {}

    public static function fromState(
        string $symbol,
        string $interval,
        string $targetAt,
        string $targetPrice,
        string $direction,
        int|string $confidence,
        ?string $note,
    ): self {
        $validated = self::validatePayload([
            'symbol' => strtoupper(trim($symbol)),
            'interval' => $interval,
            'target_at' => trim($targetAt),
            'target_price' => trim($targetPrice),
            'direction' => strtolower(trim($direction)),
            'confidence' => $confidence,
            'note' => trim((string) $note),
        ], self::rules());

        return new self(
            (string) $validated['symbol'],
            (string) $validated['interval'],
            CarbonImmutable::parse((string) $validated['target_at'], config('app.timezone'))->utc(),
            (string) $validated['target_price'],
            (string) $validated['direction'],
            (int) $validated['confidence'],
            $validated['note'] === '' ? null : (string) $validated['note'],
        );
    }

    public static function defaultTargetAt(string $interval): string
    {
        return CryptoIntervals::addSteps(now(config('app.timezone')), $interval, 1)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, string>
     */
    public static function directionOptions(): array
    {
        return [
            'above' => __('ui.direction.above'),
            'below' => __('ui.direction.below'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(): array
    {
        return [
            'symbol' => self::symbolRules(),
            'interval' => self::intervalRules(array_keys(MarketsDashboardRequest::intervalOptions())),
            'target_at' => [
                'required',
                'date',
                'after:now',
                'before_or_equal:'.now(config('app.timezone'))->addDays(30)->toDateTimeString(),
            ],
            'target_price' => ['required', 'numeric', 'gt:0'],
            'direction' => ['required', 'string', Rule::in(array_keys(self::directionOptions()))],
            'confidence' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string', 'max:160'],
        ];
    }
}
