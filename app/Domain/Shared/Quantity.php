<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/** Cantidad de ejemplares: entero mayor que cero (CA-03.3). */
final readonly class Quantity
{
    private function __construct(public int $value) {}

    public static function fromInt(int $value, string $field = 'total_copies'): self
    {
        if ($value < 1) {
            throw new ValidationFailed('La cantidad de ejemplares debe ser al menos 1.', [
                ['field' => $field, 'issue' => 'debe ser mayor o igual a 1'],
            ]);
        }

        return new self($value);
    }
}
