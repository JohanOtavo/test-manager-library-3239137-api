<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** Libro y categoría congelados más el conteo (CA-10.1). Sin socios ni bibliotecarios (CA-10.3). */
final readonly class MostBorrowedRow
{
    public function __construct(
        public string $bookId,
        public string $bookTitle,
        public string $categoryName,
        public int $timesBorrowed,
    ) {}
}
