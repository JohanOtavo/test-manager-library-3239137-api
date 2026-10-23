<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/** ISBN de 13 dígitos (CA-03.2). Sin tabla propia: vive en la fila de su dueño (D-07). */
final readonly class Isbn
{
    private function __construct(public string $value) {}

    public static function fromString(string $raw): self
    {
        $value = trim($raw);

        if (preg_match('/^[0-9]{13}$/', $value) !== 1) {
            throw new ValidationFailed('El ISBN debe tener exactamente 13 dígitos.', [
                ['field' => 'isbn', 'issue' => 'debe tener 13 dígitos'],
            ]);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
