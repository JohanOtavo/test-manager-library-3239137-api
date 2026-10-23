<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\LoanRepository;
use App\Application\Shared\OverdueRow;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanItem;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\LoanPolicy;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseLoanRepository implements LoanRepository
{
    public function __construct(
        private ConnectionInterface $connection,
        private LoanPolicy $policy,
    ) {}

    public function find(string $id): ?Loan
    {
        $row = $this->connection->table(Tables::LOAN)->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row, $this->itemsOf([$id]));
    }

    public function add(Loan $loan): void
    {
        $this->connection->table(Tables::LOAN)->insert([
            'id' => $loan->id,
            'member_id' => $loan->memberId,
            'loaned_by_user_id' => $loan->loanedByUserId,
            'loaned_at' => Tables::instant($loan->loanedAt),
        ]);

        $rows = [];

        foreach ($loan->items as $item) {
            $rows[] = [
                'id' => $item->id,
                'loan_id' => $loan->id,
                'book_id' => $item->bookId,
                'book_title' => $item->bookTitle,
                'isbn' => $item->isbn->value,
                'category_name' => $item->categoryName,
                'due_at' => Tables::instant($item->dueAt),
                'returned_at' => null,
            ];
        }

        $this->connection->table(Tables::LOAN_ITEM)->insert($rows);
    }

    public function markItemReturned(string $loanId, string $itemId, DateTimeImmutable $now): bool
    {
        $affected = $this->connection->table(Tables::LOAN_ITEM)
            ->where('id', $itemId)
            ->where('loan_id', $loanId)
            ->whereNull('returned_at')
            ->update(['returned_at' => Tables::instant($now)]);

        return $affected === 1;
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        Pagination $page,
        DateTimeImmutable $now,
    ): PageResult {
        $base = $this->connection->table(Tables::LOAN);

        if ($from !== null) {
            $base->where('loaned_at', '>=', Tables::instant($from));
        }

        if ($to !== null) {
            $base->where('loaned_at', '<=', Tables::instant($to));
        }

        if ($memberId !== null && $memberId !== '') {
            $base->where('member_id', $memberId);
        }

        if ($status !== null) {
            $this->filterByStatus($base, $status, $now);
        }

        $total = (clone $base)->count();

        $rows = $base->orderByDesc('loaned_at')
            ->offset($page->offset())
            ->limit($page->size)
            ->get()
            ->all();

        $ids = array_map(fn (object $row): string => (string) $row->id, $rows);
        $items = $this->itemsOf($ids);

        return new PageResult(
            array_map(fn (object $row): Loan => $this->hydrate($row, $items), $rows),
            $page->page,
            $page->size,
            $total,
        );
    }

    public function overdue(DateTimeImmutable $now): array
    {
        $rows = $this->connection->table(Tables::LOAN_ITEM)
            ->join(Tables::LOAN, Tables::LOAN.'.id', '=', Tables::LOAN_ITEM.'.loan_id')
            ->join(Tables::MEMBER, Tables::MEMBER.'.id', '=', Tables::LOAN.'.member_id')
            ->whereNull(Tables::LOAN_ITEM.'.returned_at')
            ->where(Tables::LOAN_ITEM.'.due_at', '<', Tables::instant($now))
            ->orderBy(Tables::LOAN_ITEM.'.due_at')
            ->select([
                Tables::LOAN_ITEM.'.id as item_id',
                Tables::LOAN_ITEM.'.loan_id',
                Tables::LOAN_ITEM.'.book_title',
                Tables::LOAN_ITEM.'.due_at',
                Tables::MEMBER.'.full_name as member_name',
            ])
            ->get();

        return array_map(function (object $row) use ($now): OverdueRow {
            $dueAt = Tables::toInstant((string) $row->due_at);

            return new OverdueRow(
                (string) $row->loan_id,
                (string) $row->item_id,
                (string) $row->book_title,
                (string) $row->member_name,
                $dueAt,
                (int) $dueAt->diff($now)->days,
            );
        }, $rows->all());
    }

    /**
     * El estado no está en la base, así que el filtro se arma con subconsultas
     * sobre las líneas (artículo VII, CA-08.3).
     */
    private function filterByStatus(Builder $base, LoanStatus $status, DateTimeImmutable $now): void
    {
        $pending = fn (Builder $query): Builder => $query->from(Tables::LOAN_ITEM)
            ->whereColumn(Tables::LOAN_ITEM.'.loan_id', Tables::LOAN.'.id')
            ->whereNull(Tables::LOAN_ITEM.'.returned_at');

        $late = fn (Builder $query): Builder => $pending($query)
            ->where(Tables::LOAN_ITEM.'.due_at', '<', Tables::instant($now));

        match ($status) {
            LoanStatus::Closed => $base->whereNotExists($pending),
            LoanStatus::Overdue => $base->whereExists($late),
            LoanStatus::Open => $base->whereExists($pending)->whereNotExists($late),
        };
    }

    /**
     * Trae las líneas de varios préstamos en una sola consulta: así listar una
     * página no dispara una consulta por fila.
     *
     * @param  list<string>  $loanIds
     * @return array<string, list<LoanItem>>
     */
    private function itemsOf(array $loanIds): array
    {
        if ($loanIds === []) {
            return [];
        }

        $rows = $this->connection->table(Tables::LOAN_ITEM)
            ->whereIn('loan_id', $loanIds)
            ->orderBy('book_title')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(string) $row->loan_id][] = LoanItem::restore(
                (string) $row->id,
                (string) $row->book_id,
                (string) $row->book_title,
                Isbn::fromString((string) $row->isbn),
                (string) $row->category_name,
                Tables::toInstant((string) $row->due_at),
                Tables::toInstant($row->returned_at === null ? null : (string) $row->returned_at),
            );
        }

        return $grouped;
    }

    /** @param array<string, list<LoanItem>> $items */
    private function hydrate(object $row, array $items): Loan
    {
        $id = (string) $row->id;

        return Loan::restore(
            $id,
            (string) $row->member_id,
            (string) $row->loaned_by_user_id,
            Tables::toInstant((string) $row->loaned_at),
            $items[$id] ?? [],
            $this->policy,
        );
    }
}
