<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * Descontar el ejemplar y crear la línea son una sola operación (data-model §4).
 * El caso de uso lo pide por aquí y no sabe qué motor hay debajo.
 */
interface TransactionManager
{
    /** @param callable(): mixed $work */
    public function transactional(callable $work): mixed;
}
