<?php

declare(strict_types=1);

namespace App\Domain\Loan;

use App\Domain\Exception\MemberHasOverdue;
use App\Domain\Exception\MemberLimitExceeded;
use App\Domain\Shared\LoanPolicy;

/**
 * Las dos reglas que miran al socio entero y no a un préstamo suelto (CA-06.4,
 * CA-06.5). Reciben el conteo y el aviso ya calculados, así se prueban con
 * números y sin base de datos.
 */
final readonly class MemberBorrowingLimit
{
    public function __construct(private LoanPolicy $policy) {}

    public function ensureCanBorrow(int $openBooks, int $requestedBooks, bool $hasOverdue): void
    {
        // Un vencido pesa más que el conteo: se avisa primero (CA-06.5).
        if ($hasOverdue) {
            throw new MemberHasOverdue('El socio tiene libros vencidos: debe devolverlos antes de pedir otro.');
        }

        if ($openBooks + $requestedBooks > $this->policy->maxOpenBooksPerMember) {
            throw new MemberLimitExceeded(
                sprintf(
                    'El socio ya tiene %d libro(s) sin devolver y el máximo es %d.',
                    $openBooks,
                    $this->policy->maxOpenBooksPerMember,
                ),
                [['field' => 'items', 'issue' => sprintf('sin devolver: %d', $openBooks)]],
            );
        }
    }
}
