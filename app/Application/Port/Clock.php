<?php

declare(strict_types=1);

namespace App\Application\Port;

use DateTimeImmutable;

/** El reloj es un puerto (D-05): una prueba comprueba 14 días sin esperar 14 días. */
interface Clock
{
    public function now(): DateTimeImmutable;
}
