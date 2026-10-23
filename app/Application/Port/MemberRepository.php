<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Member\Member;
use App\Domain\Shared\DocumentNumber;
use DateTimeImmutable;

interface MemberRepository
{
    public function find(string $id): ?Member;

    public function existsByDocument(DocumentNumber $documentNumber): bool;

    public function add(Member $member): void;

    /** @return PageResult<Member> */
    public function search(?string $query, Pagination $page): PageResult;

    /** Libros sin devolver del socio, sumando todos sus préstamos (CA-06.4). */
    public function countOpenBooks(string $memberId): int;

    /** Si tiene alguna línea sin devolver y ya vencida (CA-06.5). */
    public function hasOverdue(string $memberId, DateTimeImmutable $now): bool;
}
