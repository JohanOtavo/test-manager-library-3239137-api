<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\LoanRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Loan\Loan;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;

final class InMemoryLoans implements LoanRepository
{
    /** @var array<string, Loan> */
    private array $loans = [];

    /** @var array<string, bool> */
    private array $returnedItems = [];

    public function find(string $id): ?Loan
    {
        return $this->loans[$id] ?? null;
    }

    public function add(Loan $loan): void
    {
        $this->loans[$loan->id] = $loan;
    }

    public function markItemReturned(string $loanId, string $itemId, DateTimeImmutable $now): bool
    {
        $key = $loanId.'/'.$itemId;

        if (isset($this->returnedItems[$key])) {
            return false;
        }

        $this->returnedItems[$key] = true;
        $this->loans[$loanId] = $this->loans[$loanId]->returnItem($itemId, $now);

        return true;
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        Pagination $page,
        DateTimeImmutable $now,
    ): PageResult {
        $loans = array_values($this->loans);

        return new PageResult($loans, $page->page, $page->size, count($loans));
    }

    public function overdue(DateTimeImmutable $now): array
    {
        return [];
    }
}
