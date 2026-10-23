<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Port\CategoryRepository;
use App\Domain\Book\Book;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Quantity;

/** HU-03 · Reemplaza título, autor, categoría y total (CA-03.3). */
final readonly class UpdateBookService
{
    public function __construct(
        private BookRepository $books,
        private CategoryRepository $categories,
    ) {}

    public function handle(
        string $bookId,
        string $title,
        string $author,
        string $categoryId,
        int $totalCopies,
    ): Book {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFound('Ese libro no existe o está dado de baja.');
        }

        if ($this->categories->find($categoryId) === null) {
            throw new ValidationFailed('Esa categoría no existe.', [
                ['field' => 'category_id', 'issue' => 'categoría desconocida'],
            ]);
        }

        $updated = $book->replaceDetails($title, $author, $categoryId, Quantity::fromInt($totalCopies));
        $this->books->update($updated);

        return $updated;
    }
}
