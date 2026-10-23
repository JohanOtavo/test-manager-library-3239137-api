<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Port\CoverStorage;
use App\Domain\Exception\ResourceNotFound;

/**
 * Quita la portada. Orden obligatorio de data-model §7: primero se anula la
 * clave y se confirma, después se borra el binario.
 */
final readonly class RemoveBookCoverService
{
    public function __construct(
        private BookRepository $books,
        private CoverStorage $covers,
    ) {}

    public function handle(string $bookId): void
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFound('Ese libro no existe o está dado de baja.');
        }

        $previousKey = $book->coverKey;

        if ($previousKey === null) {
            return;
        }

        $this->books->update($book->removeCover());
        $this->covers->delete($previousKey);
    }
}
