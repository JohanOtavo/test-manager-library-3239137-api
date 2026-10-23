<?php

declare(strict_types=1);

namespace App\Adapters\Http\Middleware;

use App\Application\Port\TokenIssuer;
use App\Domain\Exception\InvalidCredentials;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Todo endpoint salvo el inicio de sesión y /health exige token (CA-01.3).
 * Quien pasa de aquí queda guardado en la petición como usuario autenticado.
 */
final readonly class AuthenticateToken
{
    public const ATTRIBUTE = 'library.authenticated_user';

    public function __construct(private TokenIssuer $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new InvalidCredentials('Hace falta un token válido.');
        }

        $user = $this->tokens->verify($token);

        // Token roto y token vencido dan la misma respuesta: no se explica cuál.
        if ($user === null) {
            throw new InvalidCredentials('Hace falta un token válido.');
        }

        $request->attributes->set(self::ATTRIBUTE, $user);

        return $next($request);
    }
}
