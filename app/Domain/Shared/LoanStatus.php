<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Se calcula de las líneas y del reloj: no hay columna (artículo VII, CA-08.3). */
enum LoanStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
