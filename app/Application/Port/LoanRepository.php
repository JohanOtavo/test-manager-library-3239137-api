<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\OverdueRow;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Loan\Loan;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;

interface LoanRepository
{
    public function find(string $id): ?Loan;

    /** Inserta el préstamo con todas sus líneas. */
    public function add(Loan $loan): void;

    /**
     * Pone returned_at solo si seguía nula. Devuelve false si otra petición
     * devolvió la línea primero: así nunca se suma dos veces (CA-07.2).
     */
    public function markItemReturned(string $loanId, string $itemId, DateTimeImmutable $now): bool;

    /** @return PageResult<Loan> */
    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        Pagination $page,
        DateTimeImmutable $now,
    ): PageResult;

    /** @return list<OverdueRow> */
    public function overdue(DateTimeImmutable $now): array;
}
