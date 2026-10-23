<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Port\Clock;
use App\Domain\Exception\BookHasOpenLoans;
use App\Domain\Exception\ResourceNotFound;

/** HU-03 · Baja lógica; un libro con préstamos abiertos no se da de baja (CA-03.5). */
final readonly class SoftDeleteBookService
{
    public function __construct(
        private BookRepository $books,
        private Clock $clock,
    ) {}

    public function handle(string $bookId): void
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFound('Ese libro no existe o está dado de baja.');
        }

        if ($this->books->hasOpenLoans($bookId)) {
            throw new BookHasOpenLoans('Ese libro tiene préstamos sin devolver: no se puede dar de baja.');
        }

        $this->books->update($book->softDelete($this->clock->now()));
    }
}
