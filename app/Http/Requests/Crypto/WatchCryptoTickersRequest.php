<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class WatchCryptoTickersRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public int $seconds,
        public int $limit,
    ) {}

    public static function fromConsole(mixed $seconds, mixed $limit): self
    {
        $validated = self::validatePayload([
            'seconds' => (int) $seconds,
            'limit' => (int) $limit,
        ], [
            'seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'limit' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        return new self((int) $validated['seconds'], (int) $validated['limit']);
    }

    /**
     * @return array<int, string>
     */
    public function symbols(): array
    {
        return self::configuredSymbols($this->limit);
    }

    public function pollSeconds(): int
    {
        return max((int) config('crypto.binance.poll_seconds'), 1);
    }
}
