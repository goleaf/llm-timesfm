<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class EvaluateCryptoForecastsRequest
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
            'limit' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        return new self((int) $validated['limit']);
    }
}
