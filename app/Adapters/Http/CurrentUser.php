<?php

declare(strict_types=1);

namespace App\Adapters\Http;

use App\Adapters\Http\Middleware\AuthenticateToken;
use App\Application\Shared\AuthenticatedUser;
use App\Domain\Exception\InvalidCredentials;
use Illuminate\Http\Request;

/** Saca de la petición al usuario que el middleware ya verificó. */
final class CurrentUser
{
    public static function of(Request $request): AuthenticatedUser
    {
        $user = $request->attributes->get(AuthenticateToken::ATTRIBUTE);

        if (! $user instanceof AuthenticatedUser) {
            throw new InvalidCredentials('Hace falta un token válido.');
        }

        return $user;
    }
}
