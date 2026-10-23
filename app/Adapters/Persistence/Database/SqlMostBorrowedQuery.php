<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\MostBorrowedQuery;
use App\Application\Shared\MostBorrowedRow;
use App\Domain\Shared\DateRange;
use Illuminate\Database\ConnectionInterface;

/**
 * Una sola consulta agregada (ADR-004).
 *
 * Agrupa por los valores congelados de la línea, nunca por el catálogo vivo:
 * por eso un reporte de octubre sigue igual si en noviembre renombran el libro
 * (CA-10.4). El rango incluye los dos extremos, así que el fin se lleva al
 * final de su día.
 */
final readonly class SqlMostBorrowedQuery implements MostBorrowedQuery
{
    public function __construct(private ConnectionInterface $connection) {}

    public function rows(DateRange $range): array
    {
        $rows = $this->connection->table(Tables::LOAN_ITEM)
            ->join(Tables::LOAN, Tables::LOAN.'.id', '=', Tables::LOAN_ITEM.'.loan_id')
            ->where(Tables::LOAN.'.loaned_at', '>=', Tables::instant($range->from))
            // El límite exclusivo incluye también los microsegundos del último
            // segundo del día, que se perdían con un tope en 23:59:59 exacto.
            ->where(Tables::LOAN.'.loaned_at', '<', Tables::instant($range->to->modify('+1 day')))
            ->groupBy(
                Tables::LOAN_ITEM.'.book_id',
                Tables::LOAN_ITEM.'.book_title',
                Tables::LOAN_ITEM.'.category_name',
            )
            ->orderByDesc('times_borrowed')
            ->orderBy(Tables::LOAN_ITEM.'.book_title')
            ->select([
                Tables::LOAN_ITEM.'.book_id',
                Tables::LOAN_ITEM.'.book_title',
                Tables::LOAN_ITEM.'.category_name',
                $this->connection->raw('count(*) as times_borrowed'),
            ])
            ->get();

        return array_map(
            fn (object $row): MostBorrowedRow => new MostBorrowedRow(
                (string) $row->book_id,
                (string) $row->book_title,
                (string) $row->category_name,
                (int) $row->times_borrowed,
            ),
            $rows->all(),
        );
    }
}
