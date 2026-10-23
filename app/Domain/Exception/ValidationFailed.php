<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** Un valor no cumple su regla de forma. */
final class ValidationFailed extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'VALIDATION_ERROR';
    }
}
