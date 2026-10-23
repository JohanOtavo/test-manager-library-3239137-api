<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\MemberHasOverdue;
use App\Domain\Exception\MemberLimitExceeded;
use App\Domain\Loan\MemberBorrowingLimit;
use App\Domain\Shared\LoanPolicy;
use PHPUnit\Framework\TestCase;

/** T-05 · Máximo 3 libros sin devolver y nada de pedir con vencidos. */
final class MemberBorrowingLimitTest extends TestCase
{
    public function test_deja_pedir_hasta_el_maximo(): void
    {
        $this->limit()->ensureCanBorrow(openBooks: 1, requestedBooks: 2, hasOverdue: false);

        $this->expectNotToPerformAssertions();
    }

    public function test_pasarse_del_maximo_falla(): void
    {
        $this->expectException(MemberLimitExceeded::class);

        $this->limit()->ensureCanBorrow(openBooks: 2, requestedBooks: 2, hasOverdue: false);
    }

    public function test_con_vencidos_no_se_presta_ni_uno(): void
    {
        $this->expectException(MemberHasOverdue::class);

        $this->limit()->ensureCanBorrow(openBooks: 1, requestedBooks: 1, hasOverdue: true);
    }

    private function limit(): MemberBorrowingLimit
    {
        return new MemberBorrowingLimit(new LoanPolicy());
    }
}
