<?php

declare(strict_types=1);

namespace App\Adapters\System;

use App\Application\Port\Clock;
use DateTimeImmutable;
use DateTimeZone;

/** El reloj de verdad, siempre en UTC (RN-6). */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
