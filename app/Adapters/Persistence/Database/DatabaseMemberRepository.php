<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\MemberRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Member\Member;
use App\Domain\Shared\DocumentNumber;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseMemberRepository implements MemberRepository
{
    public function __construct(private ConnectionInterface $connection) {}

    public function find(string $id): ?Member
    {
        $row = $this->connection->table(Tables::MEMBER)->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function existsByDocument(DocumentNumber $documentNumber): bool
    {
        return $this->connection->table(Tables::MEMBER)
            ->where('document_number', $documentNumber->value)
            ->exists();
    }

    public function add(Member $member): void
    {
        $this->connection->table(Tables::MEMBER)->insert([
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber->value,
        ]);
    }

    public function search(?string $query, Pagination $page): PageResult
    {
        $base = $this->connection->table(Tables::MEMBER);

        if ($query !== null && trim($query) !== '') {
            $term = '%'.trim($query).'%';
            $base->where(function (Builder $where) use ($term): void {
                $where->where('full_name', 'ilike', $term)
                    ->orWhere('document_number', 'ilike', $term);
            });
        }

        $total = (clone $base)->count();

        $rows = $base->orderBy('full_name')
            ->offset($page->offset())
            ->limit($page->size)
            ->get();

        return new PageResult(
            array_map(fn (object $row): Member => $this->hydrate($row), $rows->all()),
            $page->page,
            $page->size,
            $total,
        );
    }

    public function countOpenBooks(string $memberId): int
    {
        return $this->openItems($memberId)->count();
    }

    public function hasOverdue(string $memberId, DateTimeImmutable $now): bool
    {
        return $this->openItems($memberId)
            ->where(Tables::LOAN_ITEM.'.due_at', '<', Tables::instant($now))
            ->exists();
    }

    /** Líneas sin devolver del socio, sumando todos sus préstamos. */
    private function openItems(string $memberId): Builder
    {
        return $this->connection->table(Tables::LOAN_ITEM)
            ->join(Tables::LOAN, Tables::LOAN.'.id', '=', Tables::LOAN_ITEM.'.loan_id')
            ->where(Tables::LOAN.'.member_id', $memberId)
            ->whereNull(Tables::LOAN_ITEM.'.returned_at');
    }

    private function hydrate(object $row): Member
    {
        return Member::restore(
            (string) $row->id,
            (string) $row->full_name,
            DocumentNumber::fromString((string) $row->document_number),
        );
    }
}
