<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\BookRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Book\Book;
use App\Domain\Shared\Isbn;

/**
 * Catálogo en memoria.
 *
 * applyWithdrawal imita la escritura condicionada del adaptador real: solo
 * pasa si los disponibles siguen siendo los que la prueba leyó, así se puede
 * simular la carrera por el último ejemplar sin levantar PostgreSQL.
 */
final class InMemoryBooks implements BookRepository
{
    /** @var array<string, Book> */
    private array $books = [];

    /** @var array<string, bool> */
    private array $openLoans = [];

    private ?string $raceOnRead = null;

    public function seed(Book $book): void
    {
        $this->books[$book->id] = $book;
    }

    public function stored(string $id): ?Book
    {
        return $this->books[$id] ?? null;
    }

    public function withOpenLoans(string $bookId): void
    {
        $this->openLoans[$bookId] = true;
    }

    /** Mueve los disponibles por detrás, como haría otra petición en paralelo. */
    public function forceAvailableCopies(string $bookId, int $availableCopies): void
    {
        $book = $this->books[$bookId];

        $this->books[$bookId] = Book::restore(
            $book->id,
            $book->title,
            $book->author,
            $book->isbn,
            $book->categoryId,
            $book->totalCopies,
            $availableCopies,
            $book->coverKey,
            $book->deletedAt,
        );
    }

    /**
     * Hace que otra petición se lleve un ejemplar justo después de la próxima
     * lectura de ese libro: el llamador se queda con una lectura vieja, que es
     * exactamente la carrera de RNF-2.
     */
    public function simulateConcurrentWithdrawalOn(string $bookId): void
    {
        $this->raceOnRead = $bookId;
    }

    public function findActive(string $id): ?Book
    {
        $book = $this->books[$id] ?? null;

        if ($book === null || ! $book->isActive()) {
            return null;
        }

        if ($this->raceOnRead === $id) {
            $this->raceOnRead = null;
            $this->books[$id] = $book->withdraw();
        }

        return $book;
    }

    public function existsActiveIsbn(Isbn $isbn): bool
    {
        foreach ($this->books as $book) {
            if ($book->isActive() && $book->isbn->equals($isbn)) {
                return true;
            }
        }

        return false;
    }

    public function add(Book $book): void
    {
        $this->books[$book->id] = $book;
    }

    public function update(Book $book): void
    {
        $this->books[$book->id] = $book;
    }

    public function applyWithdrawal(Book $book, int $expectedAvailableCopies): bool
    {
        $current = $this->books[$book->id] ?? null;

        if ($current === null || $current->availableCopies !== $expectedAvailableCopies) {
            return false;
        }

        $this->books[$book->id] = $book;

        return true;
    }

    public function applyRestock(string $bookId): bool
    {
        $book = $this->books[$bookId] ?? null;

        if ($book === null || $book->availableCopies >= $book->totalCopies) {
            return false;
        }

        $this->books[$bookId] = $book->restock();

        return true;
    }

    public function hasOpenLoans(string $bookId): bool
    {
        return $this->openLoans[$bookId] ?? false;
    }

    public function search(?string $query, ?string $categoryId, Pagination $page): PageResult
    {
        $books = array_values(array_filter($this->books, fn (Book $book): bool => $book->isActive()));

        return new PageResult($books, $page->page, $page->size, count($books));
    }
}
