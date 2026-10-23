<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Book\Book;
use App\Domain\Exception\BookNotAvailable;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-04 · Los ejemplares nunca bajan de cero ni pasan del total. */
final class BookTest extends TestCase
{
    public function test_al_crearse_los_disponibles_igualan_al_total(): void
    {
        $book = $this->book(4);

        $this->assertSame(4, $book->totalCopies);
        $this->assertSame(4, $book->availableCopies);
        $this->assertNull($book->coverKey);
        $this->assertTrue($book->isActive());
    }

    public function test_prestar_descuenta_un_ejemplar_y_deja_el_original_intacto(): void
    {
        $book = $this->book(2);
        $afterLoan = $book->withdraw();

        $this->assertSame(1, $afterLoan->availableCopies);
        // El libro original no se mutó: la operación devolvió uno nuevo.
        $this->assertSame(2, $book->availableCopies);
    }

    public function test_sin_ejemplares_no_se_puede_prestar(): void
    {
        $book = $this->book(1)->withdraw();

        $this->expectException(BookNotAvailable::class);

        $book->withdraw();
    }

    public function test_los_disponibles_no_pasan_del_total(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->book(1)->restock();
    }

    public function test_devolver_recupera_el_ejemplar(): void
    {
        $book = $this->book(1)->withdraw()->restock();

        $this->assertSame(1, $book->availableCopies);
    }

    public function test_subir_el_total_sube_los_disponibles_en_la_misma_diferencia(): void
    {
        $book = $this->book(2)->withdraw();

        $updated = $book->replaceDetails('Otro título', 'Otro autor', 'cat-1', Quantity::fromInt(5));

        $this->assertSame(5, $updated->totalCopies);
        $this->assertSame(4, $updated->availableCopies);
    }

    public function test_bajar_el_total_por_debajo_de_lo_prestado_falla(): void
    {
        $book = $this->book(3)->withdraw()->withdraw()->withdraw();

        $this->expectException(ValidationFailed::class);

        $book->replaceDetails('Cien años de soledad', 'Gabriel García Márquez', 'cat-1', Quantity::fromInt(1));
    }

    public function test_la_baja_logica_solo_marca_la_fecha(): void
    {
        $book = $this->book(1)->softDelete(new DateTimeImmutable('2026-10-22T10:00:00Z'));

        $this->assertFalse($book->isActive());
        $this->assertSame(1, $book->totalCopies);
    }

    public function test_la_portada_no_admite_una_clave_vacia(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->book(1)->attachCover('   ');
    }

    private function book(int $totalCopies): Book
    {
        return Book::register(
            'b-1',
            'Cien años de soledad',
            'Gabriel García Márquez',
            Isbn::fromString('9780307474728'),
            'cat-1',
            Quantity::fromInt($totalCopies),
        );
    }
}
