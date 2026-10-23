<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Exception\DuplicateBookInLoan;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\LoanPolicy;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;

/**
 * Un préstamo es un hecho consumado: quién pidió, quién lo registró y cuándo.
 * No se edita ni se borra; lo único que cambia es el returned_at de una línea
 * (CA-06.7, RN-5).
 */
final readonly class Loan
{
    /** @param list<LoanItem> $items */
    private function __construct(
        public string $id,
        public string $memberId,
        public string $loanedByUserId,
        public DateTimeImmutable $loanedAt,
        public array $items,
        private LoanPolicy $policy,
    ) {}

    public static function open(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        LoanPolicy $policy,
    ): self {
        return new self($id, $memberId, $loanedByUserId, $loanedAt, [], $policy);
    }

    /** @param list<LoanItem> $items */
    public static function restore(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        array $items,
        LoanPolicy $policy,
    ): self {
        return new self($id, $memberId, $loanedByUserId, $loanedAt, $items, $policy);
    }

    /** Congela los datos del catálogo y vence a loaned_at + 14 días (CA-06.6). */
    public function addItem(
        string $itemId,
        string $bookId,
        string $bookTitle,
        Isbn $isbn,
        string $categoryName,
    ): self {
        if (count($this->items) >= $this->policy->maxItemsPerLoan) {
            throw new ValidationFailed(
                sprintf('Un préstamo admite como máximo %d libros.', $this->policy->maxItemsPerLoan),
                [['field' => 'items', 'issue' => 'demasiadas líneas']],
            );
        }

        foreach ($this->items as $item) {
            if ($item->bookId === $bookId) {
                throw new DuplicateBookInLoan('El mismo libro no puede repetirse en un préstamo.', [
                    ['field' => 'items', 'issue' => 'libro repetido'],
                ]);
            }
        }

        $dueAt = $this->loanedAt->modify(sprintf('+%d days', $this->policy->loanDays));
        $frozen = LoanItem::freeze($itemId, $bookId, $bookTitle, $isbn, $categoryName, $dueAt);

        return new self(
            $this->id,
            $this->memberId,
            $this->loanedByUserId,
            $this->loanedAt,
            array_merge($this->items, [$frozen]),
            $this->policy,
        );
    }

    /** Un préstamo trae de 1 a 3 líneas (CA-06.1). */
    public function ensureReadyToRegister(): void
    {
        if ($this->items === []) {
            throw new ValidationFailed('Un préstamo necesita al menos un libro.', [
                ['field' => 'items', 'issue' => 'lista vacía'],
            ]);
        }
    }

    /** Se devuelve una línea a la vez (CA-07.1). */
    public function returnItem(string $itemId, DateTimeImmutable $now): self
    {
        $found = false;
        $items = [];

        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                $items[] = $item->markReturned($now);
                $found = true;

                continue;
            }

            $items[] = $item;
        }

        if (! $found) {
            throw new ResourceNotFound('Esa línea no pertenece a este préstamo.');
        }

        return new self(
            $this->id,
            $this->memberId,
            $this->loanedByUserId,
            $this->loanedAt,
            $items,
            $this->policy,
        );
    }

    public function item(string $itemId): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        throw new ResourceNotFound('Esa línea no pertenece a este préstamo.');
    }

    /**
     * Cerrado si no queda nada sin devolver, vencido si algo pasó su fecha,
     * abierto en el resto de los casos (artículo VII).
     */
    public function statusAt(DateTimeImmutable $now): LoanStatus
    {
        $pending = $this->pendingItems();

        if ($pending === []) {
            return LoanStatus::Closed;
        }

        foreach ($pending as $item) {
            if ($item->isOverdueAt($now)) {
                return LoanStatus::Overdue;
            }
        }

        return LoanStatus::Open;
    }

    public function openItemsCount(): int
    {
        return count($this->pendingItems());
    }

    /** @return list<LoanItem> */
    private function pendingItems(): array
    {
        $pending = [];

        foreach ($this->items as $item) {
            if (! $item->isReturned()) {
                $pending[] = $item;
            }
        }

        return $pending;
    }
}
