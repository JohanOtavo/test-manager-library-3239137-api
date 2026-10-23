<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Domain\Book\Book;
use App\Domain\Exception\ResourceNotFound;

/** HU-04 · Un libro inexistente o dado de baja es 404 (CA-04.3). */
final readonly class GetBookService
{
    public function __construct(private BookRepository $books) {}

    public function handle(string $bookId): Book
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFound('Ese libro no existe o está dado de baja.');
        }

        return $book;
    }
}
