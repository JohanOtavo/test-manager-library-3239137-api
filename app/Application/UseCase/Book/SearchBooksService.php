<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;

/** HU-04 · Texto parcial en título o autor, filtro por categoría, solo activos (CA-04.1). */
final readonly class SearchBooksService
{
    public function __construct(private BookRepository $books) {}

    /** @return PageResult<\App\Domain\Book\Book> */
    public function handle(?string $query, ?string $categoryId, Pagination $page): PageResult
    {
        return $this->books->search($query, $categoryId, $page);
    }
}
