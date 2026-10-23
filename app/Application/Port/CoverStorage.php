<?php

declare(strict_types=1);

namespace App\Application\Port;

/** Guarda el binario fuera de la base y devuelve una clave opaca (D-08). */
interface CoverStorage
{
    public function put(string $contents, string $mimeType): string;

    public function read(string $coverKey): ?string;

    public function delete(string $coverKey): void;
}
