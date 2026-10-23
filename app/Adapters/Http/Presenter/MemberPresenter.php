<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use App\Domain\Member\Member;

/** Forma del socio en la respuesta (api-contract §3.5). */
final class MemberPresenter
{
    /** @return array<string, mixed> */
    public static function one(Member $member, ?int $activeLoans = null): array
    {
        $payload = [
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber->value,
        ];

        if ($activeLoans !== null) {
            $payload['active_loans'] = $activeLoans;
        }

        return $payload;
    }

    /**
     * @param  list<Member>  $members
     * @return list<array<string, mixed>>
     */
    public static function many(array $members): array
    {
        return array_map(fn (Member $member): array => self::one($member), $members);
    }
}
