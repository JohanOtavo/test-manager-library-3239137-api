<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\MostBorrowedRow;
use App\Domain\Shared\DateRange;

/**
 * Puerto de lectura del reporte: una consulta agregada, no un recorrido de
 * entidades (ADR-004). Lee los valores congelados de la línea, nunca el catálogo.
 */
interface MostBorrowedQuery
{
    /** @return list<MostBorrowedRow> */
    public function rows(DateRange $range): array;
}
