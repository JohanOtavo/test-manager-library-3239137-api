<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** El recurso no existe o está dado de baja. */
final class ResourceNotFound extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'NOT_FOUND';
    }
}
