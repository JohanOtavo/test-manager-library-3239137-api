<?php

declare(strict_types=1);

namespace App\Adapters\Http\Middleware;

use App\Application\Shared\AuthenticatedUser;
use App\Domain\Exception\ForbiddenRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorización por rol en cada ruta (CA-01.3). El 403 no dice si el recurso
 * existe (api-contract §4): se responde antes de ir a buscarlo.
 */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get(AuthenticateToken::ATTRIBUTE);

        if (! $user instanceof AuthenticatedUser || ! in_array($user->role->value, $roles, true)) {
            throw new ForbiddenRole('Su rol no permite esta operación.');
        }

        return $next($request);
    }
}
