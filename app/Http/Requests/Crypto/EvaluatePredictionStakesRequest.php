<?php

namespace App\Http\Requests\Crypto;

use App\Http\Requests\Crypto\Concerns\ValidatesCryptoPayload;

final readonly class EvaluatePredictionStakesRequest
{
    use ValidatesCryptoPayload;

    public function __construct(
        public int $limit,
    ) {}

    public static function fromConsole(mixed $limit): self
    {
        $validated = self::validatePayload([
            'limit' => $limit,
        ], [
            'limit' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        return new self((int) $validated['limit']);
    }
}
