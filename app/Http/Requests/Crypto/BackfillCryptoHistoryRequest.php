<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class BackfillCryptoHistoryRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public ?string $symbol,
        public string $interval,
        public int $limit,
    ) {}

    public static function fromConsole(mixed $symbol, mixed $interval, mixed $limit): self
    {
        $validated = self::validatePayload([
            'symbol' => $symbol ? strtoupper(trim((string) $symbol)) : null,
            'interval' => (string) $interval,
            'limit' => (int) $limit,
        ], [
            'symbol' => self::symbolRules(false),
            'interval' => self::intervalRules(),
            'limit' => ['required', 'integer', 'min:1', 'max:'.((int) config('crypto.binance.max_kline_limit', 1000))],
        ]);

        return new self(
            isset($validated['symbol']) ? (string) $validated['symbol'] : null,
            (string) $validated['interval'],
            (int) $validated['limit'],
        );
    }
}
