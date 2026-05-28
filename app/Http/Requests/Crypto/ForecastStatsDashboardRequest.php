<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;
use Illuminate\Validation\ValidationException;

final readonly class ForecastStatsDashboardRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public string $symbol,
        public string $interval,
    ) {}

    public static function fromRoute(?string $symbol): self
    {
        return self::fromState(self::normalizedSymbol($symbol), '1m');
    }

    public static function fromState(string $symbol, string $interval): self
    {
        $validated = self::validatePayload([
            'symbol' => strtoupper(trim($symbol)),
            'interval' => $interval,
        ], [
            'symbol' => self::symbolRules(),
            'interval' => self::intervalRules(array_keys(self::intervalOptions())),
        ]);

        return new self((string) $validated['symbol'], (string) $validated['interval']);
    }

    public function withSymbol(string $symbol): ?self
    {
        return self::tryFromState($symbol, $this->interval);
    }

    public function withInterval(string $interval): ?self
    {
        return self::tryFromState($this->symbol, $interval);
    }

    /**
     * @return array<string, string>
     */
    public static function intervalOptions(): array
    {
        return [
            '1m' => '1m',
            '5m' => '5m',
            '15m' => '15m',
            '1h' => '1h',
        ];
    }

    private static function tryFromState(string $symbol, string $interval): ?self
    {
        try {
            return self::fromState($symbol, $interval);
        } catch (ValidationException) {
            return null;
        }
    }
}
