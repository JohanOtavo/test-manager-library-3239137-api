<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Port\CoverStorage;
use App\Domain\Exception\ResourceNotFound;

/** Devuelve el binario de la portada o 404 (api-contract §3.4). */
final readonly class GetBookCoverService
{
    public function __construct(
        private BookRepository $books,
        private CoverStorage $covers,
    ) {}

    public function handle(string $bookId): string
    {
        $book = $this->books->findActive($bookId);

        if ($book === null || $book->coverKey === null) {
            throw new ResourceNotFound('Ese libro no tiene portada.');
        }

        $contents = $this->covers->read($book->coverKey);

        if ($contents === null) {
            throw new ResourceNotFound('Ese libro no tiene portada.');
        }

        return $contents;
    }
}
