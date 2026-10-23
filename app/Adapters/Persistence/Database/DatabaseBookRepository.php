<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\BookRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Book\Book;
use App\Domain\Shared\Isbn;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseBookRepository implements BookRepository
{
    public function __construct(private ConnectionInterface $connection) {}

    public function findActive(string $id): ?Book
    {
        $row = $this->activeBooks()->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function existsActiveIsbn(Isbn $isbn): bool
    {
        return $this->activeBooks()->where('isbn', $isbn->value)->exists();
    }

    public function add(Book $book): void
    {
        $this->connection->table(Tables::BOOK)->insert($this->columnsOf($book));
    }

    public function update(Book $book): void
    {
        $this->connection->table(Tables::BOOK)
            ->where('id', $book->id)
            ->update($this->columnsOf($book));
    }

    /**
     * Escritura condicionada: solo pasa si los disponibles siguen siendo los que
     * leímos. Esos disponibles hacen de testigo de versión, así que dos
     * bibliotecarios peleando por el último ejemplar dan un ganador y un
     * perdedor con 409 (ADR-002, RNF-2).
     */
    public function applyWithdrawal(Book $book, int $expectedAvailableCopies): bool
    {
        $affected = $this->connection->table(Tables::BOOK)
            ->where('id', $book->id)
            ->where('available_copies', $expectedAvailableCopies)
            ->whereNull('deleted_at')
            ->update(['available_copies' => $book->availableCopies]);

        return $affected === 1;
    }

    /** Suma un ejemplar sin pasar del total; el CHECK es la última barrera (CA-07.3). */
    public function applyRestock(string $bookId): bool
    {
        $affected = $this->connection->table(Tables::BOOK)
            ->where('id', $bookId)
            ->whereColumn('available_copies', '<', 'total_copies')
            ->update(['available_copies' => $this->connection->raw('available_copies + 1')]);

        return $affected === 1;
    }

    public function hasOpenLoans(string $bookId): bool
    {
        return $this->connection->table(Tables::LOAN_ITEM)
            ->where('book_id', $bookId)
            ->whereNull('returned_at')
            ->exists();
    }

    public function search(?string $query, ?string $categoryId, Pagination $page): PageResult
    {
        $base = $this->activeBooks();

        if ($query !== null && trim($query) !== '') {
            $term = '%'.trim($query).'%';
            $base->where(function (Builder $where) use ($term): void {
                $where->where('title', 'ilike', $term)->orWhere('author', 'ilike', $term);
            });
        }

        if ($categoryId !== null && $categoryId !== '') {
            $base->where('category_id', $categoryId);
        }

        $total = (clone $base)->count();

        $rows = $base->orderBy('title')
            ->offset($page->offset())
            ->limit($page->size)
            ->get();

        return new PageResult(
            array_map(fn (object $row): Book => $this->hydrate($row), $rows->all()),
            $page->page,
            $page->size,
            $total,
        );
    }

    /** Un libro dado de baja no existe para el catálogo (CA-03.5, CA-04.1). */
    private function activeBooks(): Builder
    {
        return $this->connection->table(Tables::BOOK)->whereNull('deleted_at');
    }

    /** @return array<string, mixed> */
    private function columnsOf(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn->value,
            'category_id' => $book->categoryId,
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
            'deleted_at' => $book->deletedAt === null ? null : Tables::instant($book->deletedAt),
        ];
    }

    private function hydrate(object $row): Book
    {
        return Book::restore(
            (string) $row->id,
            (string) $row->title,
            (string) $row->author,
            Isbn::fromString((string) $row->isbn),
            (string) $row->category_id,
            (int) $row->total_copies,
            (int) $row->available_copies,
            $row->cover_key === null ? null : (string) $row->cover_key,
            Tables::toInstant($row->deleted_at === null ? null : (string) $row->deleted_at),
        );
    }
}
