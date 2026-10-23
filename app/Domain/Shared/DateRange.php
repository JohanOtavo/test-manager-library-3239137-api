<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidDateRange;
use DateTimeImmutable;

/** Rango con los dos extremos incluidos (CA-10.1, CA-10.2). */
final readonly class DateRange
{
    private function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    public static function between(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        if ($to < $from) {
            throw new InvalidDateRange('El fin del rango no puede ser anterior al inicio.', [
                ['field' => 'to', 'issue' => 'anterior a from'],
            ]);
        }

        return new self($from, $to);
    }
}
