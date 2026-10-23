<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-01.3 · Rol insuficiente para la operación. */
final class ForbiddenRole extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'FORBIDDEN_ROLE';
    }
}
