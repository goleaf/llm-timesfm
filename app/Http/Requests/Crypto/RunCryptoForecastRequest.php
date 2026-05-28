<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;
use Illuminate\Validation\Rule;

final readonly class RunCryptoForecastRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public string $symbol,
        public string $period,
    ) {}

    public static function fromConsole(mixed $symbol, mixed $period): self
    {
        $validated = self::validatePayload([
            'symbol' => strtoupper(trim((string) $symbol)),
            'period' => (string) $period,
        ], self::rules());

        return new self((string) $validated['symbol'], (string) $validated['period']);
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

    /**
     * @return array<string, mixed>
     */
    private static function rules(): array
    {
        return [
            'symbol' => self::symbolRules(),
            'period' => ['required', 'string', Rule::in(self::configuredForecastPeriods())],
        ];
    }
}
