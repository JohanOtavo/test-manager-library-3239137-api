<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-01.2 · No distingue si falló el usuario o la clave. */
final class InvalidCredentials extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'INVALID_CREDENTIALS';
    }
}
