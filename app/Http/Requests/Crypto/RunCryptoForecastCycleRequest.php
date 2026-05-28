<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;
use Illuminate\Validation\Rule;

final readonly class RunCryptoForecastCycleRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public string $period,
        public int $limit,
        public int $freshMinutes,
    ) {}

    public static function fromConsole(mixed $period, mixed $limit, mixed $freshMinutes): self
    {
        $validated = self::validatePayload([
            'period' => (string) $period,
            'limit' => (int) $limit,
            'fresh_minutes' => (int) $freshMinutes,
        ], [
            'period' => ['required', 'string', Rule::in(self::configuredForecastPeriods())],
            'limit' => ['required', 'integer', 'min:1', 'max:50'],
            'fresh_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        return new self(
            (string) $validated['period'],
            (int) $validated['limit'],
            (int) $validated['fresh_minutes'],
        );
    }

    /**
     * @return array{interval:string,horizon:int,context:int}
     */
    public function settings(): array
    {
        $settings = config("crypto.forecasting.periods.{$this->period}");

        return [
            'interval' => (string) $settings['interval'],
            'horizon' => (int) $settings['horizon'],
            'context' => (int) $settings['context'],
        ];
    }
}
