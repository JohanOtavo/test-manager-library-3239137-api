<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-02.2 · El nombre de usuario ya existe. */
final class UsernameTaken extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'USERNAME_TAKEN';
    }
}
