<?php

declare(strict_types=1);

namespace App\Application\UseCase\Loan;

use App\Application\Port\LoanRepository;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Loan\Loan;

/** HU-08 · Un préstamo con sus líneas; el estado lo calcula quien lo pinta (CA-08.1). */
final readonly class GetLoanService
{
    public function __construct(private LoanRepository $loans) {}

    public function handle(string $loanId): Loan
    {
        $loan = $this->loans->find($loanId);

        if ($loan === null) {
            throw new ResourceNotFound('Ese préstamo no existe.');
        }

        return $loan;
    }
}
