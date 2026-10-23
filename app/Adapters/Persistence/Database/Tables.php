<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Los nombres de tabla y la conversión de fechas, en un sitio.
 *
 * El esquema se nombra explícito en vez de confiar en el search_path: así una
 * conexión mal configurada falla en vez de escribir en otro esquema.
 */
final class Tables
{
    public const CATEGORY = 'library.category';
    public const BOOK = 'library.book';
    public const MEMBER = 'library.member';
    public const LOAN = 'library.loan';
    public const LOAN_ITEM = 'library.loan_item';
    public const USER = 'library.user';

    /** Las fechas se guardan como instantes UTC con zona explícita (RN-6). */
    public static function instant(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');
    }

    public static function toInstant(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
    }
}
