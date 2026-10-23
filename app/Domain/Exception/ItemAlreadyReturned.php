<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-07.2 · La línea ya estaba devuelta. */
final class ItemAlreadyReturned extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'ITEM_ALREADY_RETURNED';
    }
}
