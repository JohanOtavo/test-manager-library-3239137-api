<?php

declare(strict_types=1);

namespace App\Application\UseCase\Report;

use App\Application\Port\MostBorrowedQuery;
use App\Domain\Shared\DateRange;
use DateTimeImmutable;

/**
 * HU-10 · Reporte de más prestados.
 *
 * Lee los valores congelados de la línea, así que un periodo cerrado no cambia
 * aunque luego renombren el libro o su categoría (CA-10.4, ADR-004).
 */
final readonly class MostBorrowedReportService
{
    public function __construct(private MostBorrowedQuery $query) {}

    /** @return list<\App\Application\Shared\MostBorrowedRow> */
    public function handle(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->query->rows(DateRange::between($from, $to));
    }
}
