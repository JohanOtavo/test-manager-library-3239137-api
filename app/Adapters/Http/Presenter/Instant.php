<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use DateTimeImmutable;
use DateTimeZone;

/** ISO 8601 en UTC terminado en Z, como pide el contrato (api-contract §1). */
final class Instant
{
    public static function format(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    public static function formatOrNull(?DateTimeImmutable $moment): ?string
    {
        return $moment === null ? null : self::format($moment);
    }
}
