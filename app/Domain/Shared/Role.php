<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/** Los dos únicos roles del sistema (spec §1). */
enum Role: string
{
    case Admin = 'admin';
    case Librarian = 'librarian';

    public static function fromString(string $raw): self
    {
        $role = self::tryFrom(trim($raw));

        if ($role === null) {
            throw new ValidationFailed('El rol debe ser admin o librarian.', [
                ['field' => 'role', 'issue' => 'rol desconocido'],
            ]);
        }

        return $role;
    }

    /** El admin lo provisiona el despliegue: nadie lo otorga en ejecución (CA-02.3). */
    public static function assignableByApi(string $raw): self
    {
        $role = self::fromString($raw);

        if ($role === self::Admin) {
            throw new ValidationFailed('El rol admin no se otorga por la API.', [
                ['field' => 'role', 'issue' => 'solo se admite librarian'],
            ]);
        }

        return $role;
    }
}
