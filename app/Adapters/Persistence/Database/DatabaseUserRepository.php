<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\UserRepository;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseUserRepository implements UserRepository
{
    public function __construct(private ConnectionInterface $connection) {}

    public function find(string $id): ?User
    {
        return $this->hydrate($this->connection->table(Tables::USER)->where('id', $id)->first());
    }

    public function findByUsername(string $username): ?User
    {
        return $this->hydrate($this->connection->table(Tables::USER)->where('username', $username)->first());
    }

    public function existsByUsername(string $username): bool
    {
        return $this->connection->table(Tables::USER)->where('username', $username)->exists();
    }

    public function add(User $user): void
    {
        $this->connection->table(Tables::USER)->insert([
            'id' => $user->id,
            'username' => $user->username,
            'password_hash' => $user->passwordHash,
            'role' => $user->role->value,
        ]);
    }

    private function hydrate(?object $row): ?User
    {
        if ($row === null) {
            return null;
        }

        return User::restore(
            (string) $row->id,
            (string) $row->username,
            (string) $row->password_hash,
            Role::from((string) $row->role),
        );
    }
}
