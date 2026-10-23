<?php

declare(strict_types=1);

namespace App\Application\Shared;

use DateTimeImmutable;

/**
 * Una línea vencida del listado operativo (CA-09.1). Aquí sí aparece el nombre
 * del socio porque hay que llamarlo; el reporte del §3.7 no lo expone.
 */
final readonly class OverdueRow
{
    public function __construct(
        public string $loanId,
        public string $itemId,
        public string $bookTitle,
        public string $memberName,
        public DateTimeImmutable $dueAt,
        public int $daysLate,
    ) {}
}
