<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Role;

/** Operador interno. El dominio solo ve el hash, nunca la clave (artículo IX). */
final readonly class User
{
    private function __construct(
        public string $id,
        public string $username,
        public string $passwordHash,
        public Role $role,
    ) {}

    public static function register(string $id, string $username, string $passwordHash, Role $role): self
    {
        if ($passwordHash === '') {
            throw new ValidationFailed('El usuario necesita una clave.', [
                ['field' => 'password', 'issue' => 'obligatoria'],
            ]);
        }

        return new self($id, self::normalizeUsername($username), $passwordHash, $role);
    }

    public static function restore(string $id, string $username, string $passwordHash, Role $role): self
    {
        return new self($id, $username, $passwordHash, $role);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Se guarda en minúsculas y recortado (CA-02.2). */
    public static function normalizeUsername(string $raw): string
    {
        $username = mb_strtolower(trim($raw));

        if ($username === '' || mb_strlen($username) > 120) {
            throw new ValidationFailed('El nombre de usuario admite entre 1 y 120 caracteres.', [
                ['field' => 'username', 'issue' => 'entre 1 y 120 caracteres'],
            ]);
        }

        return $username;
    }
}
