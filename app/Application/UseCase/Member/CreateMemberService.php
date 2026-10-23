<?php

declare(strict_types=1);

namespace App\Application\UseCase\Member;

use App\Application\Port\IdGenerator;
use App\Application\Port\MemberRepository;
use App\Domain\Exception\DocumentTaken;
use App\Domain\Member\Member;
use App\Domain\Shared\DocumentNumber;

/** HU-05 · Crear socio. El documento es único (CA-05.1). */
final readonly class CreateMemberService
{
    public function __construct(
        private MemberRepository $members,
        private IdGenerator $ids,
    ) {}

    public function handle(string $fullName, string $documentNumber): Member
    {
        $document = DocumentNumber::fromString($documentNumber);

        if ($this->members->existsByDocument($document)) {
            throw new DocumentTaken(sprintf('Ya hay un socio con el documento %s.', $document->value));
        }

        $member = Member::register($this->ids->next(), $fullName, $document);
        $this->members->add($member);

        return $member;
    }
}
