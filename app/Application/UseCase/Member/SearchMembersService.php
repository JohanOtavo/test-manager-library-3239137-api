<?php

declare(strict_types=1);

namespace App\Application\UseCase\Member;

use App\Application\Port\MemberRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;

/** HU-05 · Listar y buscar socios por nombre o documento (CA-05.2). */
final readonly class SearchMembersService
{
    public function __construct(private MemberRepository $members) {}

    /** @return PageResult<\App\Domain\Member\Member> */
    public function handle(?string $query, Pagination $page): PageResult
    {
        return $this->members->search($query, $page);
    }
}
