<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-03.2 · El ISBN ya existe entre los libros activos. */
final class IsbnTaken extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'ISBN_TAKEN';
    }
}
