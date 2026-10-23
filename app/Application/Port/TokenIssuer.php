<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\AuthenticatedUser;
use App\Application\Shared\IssuedToken;
use App\Domain\Shared\Role;

/** Emite y verifica el token de sesión (api-contract §4). */
interface TokenIssuer
{
    public function issue(string $userId, Role $role): IssuedToken;

    /** Null si el token no es válido o ya venció. */
    public function verify(string $token): ?AuthenticatedUser;
}
