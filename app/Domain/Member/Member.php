<?php

declare(strict_types=1);

namespace App\Domain\Member;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\DocumentNumber;

/** Quien pide prestado. No inicia sesión y no se elimina (spec §1, CA-05.3). */
final readonly class Member
{
    private function __construct(
        public string $id,
        public string $fullName,
        public DocumentNumber $documentNumber,
    ) {}

    public static function register(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        return new self($id, self::cleanName($fullName), $documentNumber);
    }

    public static function restore(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        return new self($id, $fullName, $documentNumber);
    }

    private static function cleanName(string $raw): string
    {
        $name = trim($raw);

        if ($name === '' || mb_strlen($name) > 200) {
            throw new ValidationFailed('El nombre del socio admite entre 1 y 200 caracteres.', [
                ['field' => 'full_name', 'issue' => 'entre 1 y 200 caracteres'],
            ]);
        }

        return $name;
    }
}
