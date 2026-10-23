<?php

declare(strict_types=1);

namespace App\Application\UseCase\Loan;

use App\Application\Port\Clock;
use App\Application\Port\LoanRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;

/** HU-08 · Listar por rango, estado y socio, orden por fecha descendente (CA-08.2). */
final readonly class SearchLoansService
{
    public function __construct(
        private LoanRepository $loans,
        private Clock $clock,
    ) {}

    /** @return PageResult<\App\Domain\Loan\Loan> */
    public function handle(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        Pagination $page,
    ): PageResult {
        return $this->loans->search($from, $to, $status, $memberId, $page, $this->clock->now());
    }
}
