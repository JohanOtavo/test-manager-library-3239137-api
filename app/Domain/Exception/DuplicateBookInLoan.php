<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-06.2 · El mismo libro aparece dos veces en el préstamo. */
final class DuplicateBookInLoan extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'DUPLICATE_BOOK_IN_LOAN';
    }
}
