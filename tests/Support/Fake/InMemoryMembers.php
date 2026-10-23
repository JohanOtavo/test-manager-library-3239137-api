<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\MemberRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Member\Member;
use App\Domain\Shared\DocumentNumber;
use DateTimeImmutable;

/**
 * Los conteos se fijan desde la prueba: contar líneas sin devolver es trabajo
 * de la base, y aquí lo que se prueba es la regla, no la consulta.
 */
final class InMemoryMembers implements MemberRepository
{
    /** @var array<string, Member> */
    private array $members = [];

    private int $openBooks = 0;

    private bool $overdue = false;

    public function add(Member $member): void
    {
        $this->members[$member->id] = $member;
    }

    public function withOpenBooks(int $openBooks): void
    {
        $this->openBooks = $openBooks;
    }

    public function withOverdue(bool $overdue): void
    {
        $this->overdue = $overdue;
    }

    public function find(string $id): ?Member
    {
        return $this->members[$id] ?? null;
    }

    public function existsByDocument(DocumentNumber $documentNumber): bool
    {
        foreach ($this->members as $member) {
            if ($member->documentNumber->value === $documentNumber->value) {
                return true;
            }
        }

        return false;
    }

    public function search(?string $query, Pagination $page): PageResult
    {
        $members = array_values($this->members);

        return new PageResult($members, $page->page, $page->size, count($members));
    }

    public function countOpenBooks(string $memberId): int
    {
        return $this->openBooks;
    }

    public function hasOverdue(string $memberId, DateTimeImmutable $now): bool
    {
        return $this->overdue;
    }
}
