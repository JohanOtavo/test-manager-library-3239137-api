<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Port\CategoryRepository;
use App\Application\Port\IdGenerator;
use App\Domain\Book\Book;
use App\Domain\Exception\IsbnTaken;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\Quantity;

/** HU-03 · Crear libro (CA-03.1, CA-03.2). */
final readonly class CreateBookService
{
    public function __construct(
        private BookRepository $books,
        private CategoryRepository $categories,
        private IdGenerator $ids,
    ) {}

    public function handle(
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        int $totalCopies,
    ): Book {
        $isbnValue = Isbn::fromString($isbn);

        if ($this->categories->find($categoryId) === null) {
            throw new ValidationFailed('Esa categoría no existe.', [
                ['field' => 'category_id', 'issue' => 'categoría desconocida'],
            ]);
        }

        if ($this->books->existsActiveIsbn($isbnValue)) {
            throw new IsbnTaken(sprintf('Ya hay un libro activo con el ISBN %s.', $isbnValue->value));
        }

        $book = Book::register(
            $this->ids->next(),
            $title,
            $author,
            $isbnValue,
            $categoryId,
            Quantity::fromInt($totalCopies),
        );

        $this->books->add($book);

        return $book;
    }
}
