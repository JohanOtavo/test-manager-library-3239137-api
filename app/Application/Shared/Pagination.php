<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** Página pedida. El tamaño se recorta al máximo del contrato: pedir 500 trae 50 (CA-04.1). */
final readonly class Pagination
{
    private function __construct(
        public int $page,
        public int $size,
    ) {}

    public static function of(?int $page, ?int $size, int $maxSize, int $defaultSize): self
    {
        return new self(
            max(1, $page ?? 1),
            min($maxSize, max(1, $size ?? $defaultSize)),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }
}
