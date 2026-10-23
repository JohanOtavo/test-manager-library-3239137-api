<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Application\UseCase\Auth\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-01 · POST /auth/login. */
final readonly class AuthController
{
    public function __construct(private LoginService $login) {}

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->login->handle($input['username'], $input['password']);

        return new JsonResponse([
            'access_token' => $result->token->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $result->token->expiresInSeconds,
            'role' => $result->role->value,
        ]);
    }
}
