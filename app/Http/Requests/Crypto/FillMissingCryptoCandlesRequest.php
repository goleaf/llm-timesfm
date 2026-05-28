<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class FillMissingCryptoCandlesRequest
{
    use ValidatesCryptoPayload;

    /**
     * @param  array<int, string>|null  $symbols
     * @param  array<int, string>|null  $intervals
     */
    public function __construct(
        public ?array $symbols,
        public ?array $intervals,
        public int $window,
    ) {}

    public static function fromConsole(mixed $symbol, mixed $intervals, mixed $window): self
    {
        $symbols = $symbol ? [strtoupper(trim((string) $symbol))] : null;
        $intervals = self::normalizedStringList($intervals);
        $validated = self::validatePayload([
            'symbols' => $symbols,
            'intervals' => $intervals === [] ? null : array_map('strtolower', $intervals),
            'window' => (int) $window,
        ], [
            'symbols' => ['nullable', 'array', 'max:20'],
            'symbols.*' => self::symbolRules(),
            'intervals' => ['nullable', 'array', 'max:10'],
            'intervals.*' => self::intervalRules(),
            'window' => ['required', 'integer', 'min:1', 'max:'.((int) config('crypto.binance.max_kline_limit', 1000))],
        ]);

        return new self(
            isset($validated['symbols']) ? array_values($validated['symbols']) : null,
            isset($validated['intervals']) ? array_values($validated['intervals']) : null,
            (int) $validated['window'],
        );
    }
}
