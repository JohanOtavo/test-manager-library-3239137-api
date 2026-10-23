<?php

declare(strict_types=1);

namespace App\Adapters\Security;

use App\Application\Port\PasswordHasher;

/**
 * Argon2id cuando el PHP del contenedor lo trae compilado, y bcrypt si no
 * (D-09). Lo que no cambia nunca: solo se guarda el hash, y la clave en claro
 * no sale de este archivo (artículo IX).
 */
final class NativePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, $this->algorithm());
    }

    public function matches(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    private function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
