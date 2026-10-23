<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-06.5 · El socio tiene libros vencidos. */
final class MemberHasOverdue extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'MEMBER_HAS_OVERDUE';
    }
}
