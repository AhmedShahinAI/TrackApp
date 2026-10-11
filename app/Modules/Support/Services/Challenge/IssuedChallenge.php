<?php

declare(strict_types=1);

namespace App\Modules\Support\Services\Challenge;

/**
 * T370 — what GET /support/challenge returns: `{ challenge, difficulty, expires_in }`
 * (contracts/support-public.md).
 */
final readonly class IssuedChallenge
{
    public function __construct(
        public string $challenge,
        public int $difficulty,
        public int $expiresIn,
    ) {}

    /** @return array{challenge: string, difficulty: int, expires_in: int} */
    public function toArray(): array
    {
        return [
            'challenge' => $this->challenge,
            'difficulty' => $this->difficulty,
            'expires_in' => $this->expiresIn,
        ];
    }
}
