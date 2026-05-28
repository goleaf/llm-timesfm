<?php

namespace App\Http\Requests\Crypto\Concerns;

use App\Support\CryptoIntervals;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ValidatesCryptoPayload
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected static function validatePayload(array $payload, array $rules): array
    {
        return Validator::make($payload, $rules)->validate();
    }

    protected static function normalizedSymbol(?string $symbol, string $default = 'BTCUSDT'): string
    {
        $symbol = strtoupper(trim((string) ($symbol ?: $default)));

        return preg_match('/^[A-Z0-9]{2,20}$/', $symbol) === 1 ? $symbol : $default;
    }

    /**
     * @return array<int, string>
     */
    protected static function normalizedStringList(mixed $values): array
    {
        return collect((array) $values)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->map(fn (string $value): string => strtoupper($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function supportedIntervals(): array
    {
        return CryptoIntervals::supported();
    }

    /**
     * @return array<int, string>
     */
    protected static function configuredForecastPeriods(): array
    {
        return array_keys(config('crypto.forecasting.periods', []));
    }

    /**
     * @return array<int, string>
     */
    protected static function configuredSymbols(int $limit): array
    {
        return array_slice(config('crypto.binance.symbols', []), 0, max($limit, 1));
    }

    /**
     * @return array<int, string>
     */
    protected static function symbolRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:/^[A-Z0-9]+$/',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function intervalRules(?array $allowed = null): array
    {
        return [
            'required',
            'string',
            Rule::in($allowed ?: self::supportedIntervals()),
        ];
    }
}
