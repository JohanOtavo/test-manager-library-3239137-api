<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** CA-05.1 · El documento del socio ya existe. */
final class DocumentTaken extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'DOCUMENT_TAKEN';
    }
}
