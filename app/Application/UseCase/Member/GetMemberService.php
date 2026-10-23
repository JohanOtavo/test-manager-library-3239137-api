<?php

declare(strict_types=1);

namespace App\Application\UseCase\Member;

use App\Application\Port\MemberRepository;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Member\Member;

/** Detalle del socio con su conteo de libros sin devolver (api-contract §3.5). */
final readonly class GetMemberService
{
    public function __construct(private MemberRepository $members) {}

    /** @return array{member: Member, active_loans: int} */
    public function handle(string $memberId): array
    {
        $member = $this->members->find($memberId);

        if ($member === null) {
            throw new ResourceNotFound('Ese socio no existe.');
        }

        return [
            'member' => $member,
            'active_loans' => $this->members->countOpenBooks($memberId),
        ];
    }
}
