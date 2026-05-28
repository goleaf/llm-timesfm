<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class WarmCryptoDashboardCacheRequest
{
    use ValidatesCryptoPayload;

    /**
     * @param  array<int, string>|null  $symbols
     * @param  array<int, string>|null  $intervals
     */
    public function __construct(
        public ?array $symbols,
        public ?array $intervals,
        public int $limit,
    ) {}

    public static function fromConsole(mixed $symbols, mixed $intervals, mixed $limit): self
    {
        $symbols = self::normalizedStringList($symbols);
        $intervals = self::normalizedStringList($intervals);
        $validated = self::validatePayload([
            'symbols' => $symbols === [] ? null : $symbols,
            'intervals' => $intervals === [] ? null : array_map('strtolower', $intervals),
            'limit' => (int) $limit,
        ], [
            'symbols' => ['nullable', 'array', 'max:20'],
            'symbols.*' => self::symbolRules(),
            'intervals' => ['nullable', 'array', 'max:10'],
            'intervals.*' => self::intervalRules(),
            'limit' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return new self(
            isset($validated['symbols']) ? array_values($validated['symbols']) : null,
            isset($validated['intervals']) ? array_values($validated['intervals']) : null,
            (int) $validated['limit'],
        );
    }
}
