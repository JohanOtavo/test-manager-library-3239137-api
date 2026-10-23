<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\User\User;

interface UserRepository
{
    public function find(string $id): ?User;

    public function findByUsername(string $username): ?User;

    public function existsByUsername(string $username): bool;

    public function add(User $user): void;
}
