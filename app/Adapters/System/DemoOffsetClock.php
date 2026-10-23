<?php

declare(strict_types=1);

namespace App\Adapters\System;

use App\Application\Port\Clock;
use DateTimeImmutable;

/**
 * Reloj corrido unos días, solo para sembrar datos de demostración.
 *
 * Por qué existe: la evidencia de T-21 pide 3 líneas vencidas, y el vencimiento
 * lo calcula el dominio como loaned_at + 14 días. Por la API no hay forma de
 * pedir un préstamo con fecha vieja, y data-model §9 prohíbe que el sembrador
 * escriba SQL directo. La salida es la que ya propone el spec: el reloj es un
 * puerto (D-05), así que se cambia el reloj.
 *
 * Queda cerrado con llave: el proveedor solo lo usa si DEMO_CLOCK está activo,
 * que es algo que únicamente se enciende en local. Sin esa variable, la
 * cabecera no hace nada.
 */
final readonly class DemoOffsetClock implements Clock
{
    public function __construct(
        private Clock $inner,
        private int $offsetDays,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->inner->now()->modify(sprintf('%+d days', $this->offsetDays));
    }
}
