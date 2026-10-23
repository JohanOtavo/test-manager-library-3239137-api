<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Los números de RN-2, fuera del código del dominio para poder estrecharlos en pruebas. */
final readonly class LoanPolicy
{
    public function __construct(
        public int $loanDays = 14,
        public int $maxOpenBooksPerMember = 3,
        public int $maxItemsPerLoan = 3,
    ) {}
}
