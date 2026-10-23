<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-10.2 · El fin del rango es anterior al inicio. */
final class InvalidDateRange extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'INVALID_DATE_RANGE';
    }
}
