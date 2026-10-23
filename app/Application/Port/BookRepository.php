<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Book\Book;
use App\Domain\Shared\Isbn;

interface BookRepository
{
    /** Solo libros activos: uno dado de baja es un 404 (CA-04.3). */
    public function findActive(string $id): ?Book;

    public function existsActiveIsbn(Isbn $isbn): bool;

    public function add(Book $book): void;

    public function update(Book $book): void;

    /**
     * Guarda el descuento de un ejemplar solo si nadie movió los disponibles
     * entre la lectura y la escritura: los disponibles hacen de testigo de
     * versión (ADR-002). Devuelve false cuando otro ganó la carrera.
     */
    public function applyWithdrawal(Book $book, int $expectedAvailableCopies): bool;

    /** Suma un ejemplar sin pasar del total; el CHECK es la última barrera (CA-07.3). */
    public function applyRestock(string $bookId): bool;

    /** Si el libro tiene líneas sin devolver (CA-03.5). */
    public function hasOpenLoans(string $bookId): bool;

    /** @return PageResult<Book> */
    public function search(?string $query, ?string $categoryId, Pagination $page): PageResult;
}
