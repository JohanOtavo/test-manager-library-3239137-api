<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidDateRange;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\DateRange;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\Quantity;
use App\Domain\Shared\Role;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-03 · Objetos de valor: ISBN de 13 dígitos, cantidad > 0, fin >= inicio. */
final class ValueObjectsTest extends TestCase
{
    public function test_acepta_un_isbn_de_trece_digitos(): void
    {
        $this->assertSame('9780307474728', Isbn::fromString(' 9780307474728 ')->value);
    }

    public function test_rechaza_un_isbn_que_no_tiene_trece_digitos(): void
    {
        $this->expectException(ValidationFailed::class);

        Isbn::fromString('978030747472');
    }

    public function test_rechaza_un_isbn_con_letras(): void
    {
        $this->expectException(ValidationFailed::class);

        Isbn::fromString('97803074747X');
    }

    public function test_la_cantidad_debe_ser_al_menos_uno(): void
    {
        $this->assertSame(1, Quantity::fromInt(1)->value);

        $this->expectException(ValidationFailed::class);

        Quantity::fromInt(0);
    }

    public function test_el_rango_admite_un_solo_dia(): void
    {
        $day = new DateTimeImmutable('2026-10-14T00:00:00Z');

        $this->assertSame($day, DateRange::between($day, $day)->from);
    }

    public function test_el_rango_invertido_falla(): void
    {
        $this->expectException(InvalidDateRange::class);

        DateRange::between(
            new DateTimeImmutable('2026-10-31T00:00:00Z'),
            new DateTimeImmutable('2026-10-01T00:00:00Z'),
        );
    }

    public function test_la_api_no_puede_otorgar_el_rol_admin(): void
    {
        $this->assertSame(Role::Librarian, Role::assignableByApi('librarian'));

        $this->expectException(ValidationFailed::class);

        Role::assignableByApi('admin');
    }
}
