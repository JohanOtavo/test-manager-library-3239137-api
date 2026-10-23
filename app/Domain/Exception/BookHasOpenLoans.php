<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-03.5 · Un libro con préstamos abiertos no se da de baja. */
final class BookHasOpenLoans extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'BOOK_HAS_OPEN_LOANS';
    }
}
