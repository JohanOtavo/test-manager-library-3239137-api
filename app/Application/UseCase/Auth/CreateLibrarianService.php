<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\IdGenerator;
use App\Application\Port\PasswordHasher;
use App\Application\Port\UserRepository;
use App\Domain\Exception\UsernameTaken;
use App\Domain\Shared\Role;
use App\Domain\User\User;

/** HU-02 · Dar de alta bibliotecarios. */
final readonly class CreateLibrarianService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private IdGenerator $ids,
    ) {}

    public function handle(string $username, string $password, string $role): User
    {
        // Pedir admin por la API se rechaza antes de tocar la base (CA-02.3).
        $wantedRole = Role::assignableByApi($role);
        $normalized = User::normalizeUsername($username);

        if ($this->users->existsByUsername($normalized)) {
            throw new UsernameTaken(sprintf('El usuario "%s" ya existe.', $normalized));
        }

        $user = User::register($this->ids->next(), $normalized, $this->hasher->hash($password), $wantedRole);
        $this->users->add($user);

        return $user;
    }
}
