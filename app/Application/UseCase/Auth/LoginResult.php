<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Shared\IssuedToken;
use App\Domain\Shared\Role;

final readonly class LoginResult
{
    public function __construct(
        public IssuedToken $token,
        public Role $role,
    ) {}
}
