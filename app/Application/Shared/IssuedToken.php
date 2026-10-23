<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** Lo que devuelve el inicio de sesión (CA-01.1). */
final readonly class IssuedToken
{
    public function __construct(
        public string $accessToken,
        public int $expiresInSeconds,
    ) {}
}
