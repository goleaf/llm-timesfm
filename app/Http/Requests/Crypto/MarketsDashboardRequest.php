<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class MarketsDashboardRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public string $symbol,
        public string $interval,
        public string $forecastPeriod,
    ) {}

    public static function fromRoute(?string $symbol): self
    {
        return self::fromState(
            self::normalizedSymbol($symbol),
            '1m',
            '1h',
        );
    }

    public static function fromState(string $symbol, string $interval, string $forecastPeriod): self
    {
        $validated = self::validatePayload([
            'symbol' => strtoupper(trim($symbol)),
            'interval' => $interval,
            'forecast_period' => $forecastPeriod,
        ], self::rules());

        return new self(
            (string) $validated['symbol'],
            (string) $validated['interval'],
            (string) $validated['forecast_period'],
        );
    }

    public function withSymbol(string $symbol): ?self
    {
        return self::tryFromState($symbol, $this->interval, $this->forecastPeriod);
    }

    public function withInterval(string $interval): ?self
    {
        return self::tryFromState($this->symbol, $interval, $this->forecastPeriod);
    }

    public function withForecastPeriod(string $forecastPeriod): ?self
    {
        return self::tryFromState($this->symbol, $this->interval, $forecastPeriod);
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
            '1d' => '1d',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forecastOptions(): array
    {
        return collect(self::configuredForecastPeriods())
            ->mapWithKeys(fn (string $period): array => [$period => $period])
            ->all();
    }

    private static function tryFromState(string $symbol, string $interval, string $forecastPeriod): ?self
    {
        try {
            return self::fromState($symbol, $interval, $forecastPeriod);
        } catch (ValidationException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(): array
    {
        return [
            'symbol' => self::symbolRules(),
            'interval' => self::intervalRules(array_keys(self::intervalOptions())),
            'forecast_period' => ['required', 'string', Rule::in(self::configuredForecastPeriods())],
        ];
    }
}
