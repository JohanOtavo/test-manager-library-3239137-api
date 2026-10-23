<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\DuplicateBookInLoan;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Loan\Loan;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\LoanPolicy;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * T-05 · Estado calculado, 14 días de plazo y máximo de líneas.
 *
 * El reloj entra como parámetro, así que el vencimiento se comprueba sin
 * esperar dos semanas.
 */
final class LoanTest extends TestCase
{
    private const LOANED_AT = '2026-10-14T14:15:00Z';

    public function test_la_linea_vence_catorce_dias_despues(): void
    {
        $loan = $this->loanWithOneBook();

        $this->assertSame('2026-10-28T14:15:00+00:00', $loan->items[0]->dueAt->format('c'));
    }

    public function test_la_linea_congela_titulo_isbn_y_categoria(): void
    {
        $item = $this->loanWithOneBook()->items[0];

        $this->assertSame('Cien años de soledad', $item->bookTitle);
        $this->assertSame('9780307474728', $item->isbn->value);
        $this->assertSame('Novela', $item->categoryName);
    }

    public function test_el_mismo_libro_no_se_repite(): void
    {
        $loan = $this->loanWithOneBook();

        $this->expectException(DuplicateBookInLoan::class);

        $loan->addItem('i-2', 'b-1', 'Cien años de soledad', Isbn::fromString('9780307474728'), 'Novela');
    }

    public function test_no_admite_mas_de_tres_lineas(): void
    {
        $loan = $this->loanWithOneBook()
            ->addItem('i-2', 'b-2', 'Rayuela', Isbn::fromString('9788437604572'), 'Novela')
            ->addItem('i-3', 'b-3', 'El Aleph', Isbn::fromString('9789875666061'), 'Novela');

        $this->expectException(ValidationFailed::class);

        $loan->addItem('i-4', 'b-4', 'Ficciones', Isbn::fromString('9788420633112'), 'Novela');
    }

    public function test_un_prestamo_sin_lineas_no_se_registra(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->emptyLoan()->ensureReadyToRegister();
    }

    public function test_esta_abierto_mientras_no_vence(): void
    {
        $loan = $this->loanWithOneBook();

        $this->assertSame(LoanStatus::Open, $loan->statusAt(new DateTimeImmutable('2026-10-20T00:00:00Z')));
    }

    public function test_esta_vencido_pasada_la_fecha(): void
    {
        $loan = $this->loanWithOneBook();

        $this->assertSame(LoanStatus::Overdue, $loan->statusAt(new DateTimeImmutable('2026-10-30T00:00:00Z')));
    }

    public function test_queda_cerrado_cuando_se_devuelve_todo(): void
    {
        $loan = $this->loanWithOneBook()->returnItem('i-1', new DateTimeImmutable('2026-10-20T09:00:00Z'));

        $this->assertSame(LoanStatus::Closed, $loan->statusAt(new DateTimeImmutable('2026-10-30T00:00:00Z')));
        $this->assertSame(0, $loan->openItemsCount());
    }

    public function test_una_linea_devuelta_no_se_devuelve_dos_veces(): void
    {
        $loan = $this->loanWithOneBook()->returnItem('i-1', new DateTimeImmutable('2026-10-20T09:00:00Z'));

        $this->expectException(ItemAlreadyReturned::class);

        $loan->returnItem('i-1', new DateTimeImmutable('2026-10-21T09:00:00Z'));
    }

    public function test_cuenta_los_dias_de_retraso(): void
    {
        $item = $this->loanWithOneBook()->items[0];

        $this->assertSame(3, $item->daysLateAt(new DateTimeImmutable('2026-10-31T14:15:00Z')));
        $this->assertSame(0, $item->daysLateAt(new DateTimeImmutable('2026-10-20T14:15:00Z')));
    }

    private function emptyLoan(): Loan
    {
        return Loan::open('l-1', 'm-1', 'u-1', new DateTimeImmutable(self::LOANED_AT), new LoanPolicy());
    }

    private function loanWithOneBook(): Loan
    {
        return $this->emptyLoan()->addItem(
            'i-1',
            'b-1',
            'Cien años de soledad',
            Isbn::fromString('9780307474728'),
            'Novela',
        );
    }
}
