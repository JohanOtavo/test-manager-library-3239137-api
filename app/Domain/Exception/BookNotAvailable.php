<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-06.3 · No quedan ejemplares disponibles. */
final class BookNotAvailable extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'BOOK_NOT_AVAILABLE';
    }
}
