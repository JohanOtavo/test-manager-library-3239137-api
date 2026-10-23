<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Shared\Isbn;
use DateTimeImmutable;

/**
 * Un libro dentro de un préstamo, con los datos congelados del instante en que
 * se prestó. No siguen al catálogo: si mañana renombran el libro, esta línea no
 * cambia (artículo VIII, RN-4).
 */
final readonly class LoanItem
{
    private function __construct(
        public string $id,
        public string $bookId,
        public string $bookTitle,
        public Isbn $isbn,
        public string $categoryName,
        public DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt,
    ) {}

    public static function freeze(
        string $id,
        string $bookId,
        string $bookTitle,
        Isbn $isbn,
        string $categoryName,
        DateTimeImmutable $dueAt,
    ): self {
        return new self($id, $bookId, $bookTitle, $isbn, $categoryName, $dueAt, null);
    }

    public static function restore(
        string $id,
        string $bookId,
        string $bookTitle,
        Isbn $isbn,
        string $categoryName,
        DateTimeImmutable $dueAt,
        ?DateTimeImmutable $returnedAt,
    ): self {
        return new self($id, $bookId, $bookTitle, $isbn, $categoryName, $dueAt, $returnedAt);
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return ! $this->isReturned() && $this->dueAt < $now;
    }

    /** Días completos de retraso; cero si no está vencida (CA-09.1). */
    public function daysLateAt(DateTimeImmutable $now): int
    {
        if (! $this->isOverdueAt($now)) {
            return 0;
        }

        return (int) $this->dueAt->diff($now)->days;
    }

    /** Devolver dos veces la misma línea no suma dos veces (CA-07.2). */
    public function markReturned(DateTimeImmutable $now): self
    {
        if ($this->isReturned()) {
            throw new ItemAlreadyReturned('Esta línea del préstamo ya estaba devuelta.');
        }

        return new self(
            $this->id,
            $this->bookId,
            $this->bookTitle,
            $this->isbn,
            $this->categoryName,
            $this->dueAt,
            $now,
        );
    }
}
