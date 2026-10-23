<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Role;

/** Quien opera: sale del token, nunca del cuerpo de la petición. */
final readonly class AuthenticatedUser
{
    public function __construct(
        public string $id,
        public Role $role,
    ) {}
}
