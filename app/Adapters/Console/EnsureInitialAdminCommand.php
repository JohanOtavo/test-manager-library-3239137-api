<?php

declare(strict_types=1);

namespace App\Adapters\Console;

use App\Application\Port\IdGenerator;
use App\Application\Port\PasswordHasher;
use App\Application\Port\UserRepository;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use Illuminate\Console\Command;

/**
 * T-17 · Crea el administrador inicial desde el entorno.
 *
 * La base no puede sembrarlo porque el hash solo lo produce el puerto de hash
 * (data-model §9), y la API no otorga el rol admin (CA-02.3). Por eso existe
 * este paso del arranque: lo corre el contenedor después de migrar.
 *
 * Es idempotente: si el usuario ya está, no toca nada ni cambia su clave.
 */
final class EnsureInitialAdminCommand extends Command
{
    protected $signature = 'library:ensure-admin';

    protected $description = 'Crea el administrador inicial a partir de ADMIN_USERNAME y ADMIN_PASSWORD.';

    public function handle(UserRepository $users, PasswordHasher $hasher, IdGenerator $ids): int
    {
        $username = (string) config('library.admin_username');
        $password = (string) config('library.admin_password');

        if ($username === '' || $password === '') {
            $this->error('Faltan ADMIN_USERNAME o ADMIN_PASSWORD en el entorno.');

            return self::FAILURE;
        }

        $normalized = User::normalizeUsername($username);

        if ($users->existsByUsername($normalized)) {
            $this->info(sprintf('El administrador "%s" ya existe: no se toca nada.', $normalized));

            return self::SUCCESS;
        }

        $users->add(User::register($ids->next(), $normalized, $hasher->hash($password), Role::Admin));

        // Nunca se imprime la clave, ni aquí ni en los logs (RNF-3).
        $this->info(sprintf('Administrador "%s" creado.', $normalized));

        return self::SUCCESS;
    }
}
