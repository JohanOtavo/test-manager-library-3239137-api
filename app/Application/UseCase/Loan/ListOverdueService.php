<?php

declare(strict_types=1);

namespace App\Application\UseCase\Loan;

use App\Application\Port\Clock;
use App\Application\Port\LoanRepository;

/** HU-09 · Líneas sin devolver y vencidas, más retraso primero (CA-09.1, CA-09.2). */
final readonly class ListOverdueService
{
    public function __construct(
        private LoanRepository $loans,
        private Clock $clock,
    ) {}

    /** @return list<\App\Application\Shared\OverdueRow> */
    public function handle(): array
    {
        return $this->loans->overdue($this->clock->now());
    }
}
