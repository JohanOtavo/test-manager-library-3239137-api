<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

/** T-06 · Normalización del nombre de usuario (CA-02.2). */
final class UserTest extends TestCase
{
    public function test_el_nombre_queda_recortado_y_en_minusculas(): void
    {
        $user = User::register('u-1', 'Ana ', 'hash-falso', Role::Librarian);

        $this->assertSame('ana', $user->username);
    }

    public function test_el_nombre_vacio_falla(): void
    {
        $this->expectException(ValidationFailed::class);

        User::register('u-1', '   ', 'hash-falso', Role::Librarian);
    }

    public function test_el_usuario_necesita_un_hash(): void
    {
        $this->expectException(ValidationFailed::class);

        User::register('u-1', 'ana', '', Role::Librarian);
    }
}
