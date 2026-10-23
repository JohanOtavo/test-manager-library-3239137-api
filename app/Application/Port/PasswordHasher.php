<?php

declare(strict_types=1);

namespace App\Application\Port;

/** La clave en claro muere aquí: ni el dominio ni la base la ven (artículo IX, RNF-3). */
interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function matches(string $plainPassword, string $hash): bool;
}
