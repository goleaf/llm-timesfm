<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class SyncCryptoMetadataRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public int $limit,
    ) {}

    public static function fromConsole(mixed $limit): self
    {
        $validated = self::validatePayload([
            'limit' => (int) $limit,
        ], [
            'limit' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        return new self((int) $validated['limit']);
    }

    /**
     * @return array<int, string>
     */
    public function symbols(): array
    {
        return self::configuredSymbols($this->limit);
    }
}
