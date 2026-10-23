<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-06.4 · El socio llegó al máximo de libros sin devolver. */
final class MemberLimitExceeded extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'MEMBER_LIMIT_EXCEEDED';
    }
}
