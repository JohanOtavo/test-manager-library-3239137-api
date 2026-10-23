<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\TransactionManager;

/** Ejecuta el trabajo tal cual: sin base de datos no hay nada que confirmar. */
final class InstantTransactions implements TransactionManager
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
