<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** Categoría tal como se lee: semilla fija, sin mantenimiento (data-model §9). */
final readonly class CategoryView
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
