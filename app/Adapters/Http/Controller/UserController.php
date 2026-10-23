<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Application\UseCase\Auth\CreateLibrarianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-02 · POST /users. Solo admin. */
final readonly class UserController
{
    public function __construct(private CreateLibrarianService $createLibrarian) {}

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        $user = $this->createLibrarian->handle($input['username'], $input['password'], $input['role']);

        // Ni password ni password_hash salen en la respuesta (CA-02.3).
        return new JsonResponse([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->value,
        ], 201);
    }
}
