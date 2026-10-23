<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Loan\ReturnLoanItemService;
use App\Domain\Book\Book;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Loan\Loan;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\LoanPolicy;
use App\Domain\Shared\LoanStatus;
use App\Domain\Shared\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;
use Tests\Support\Fake\InMemoryBooks;
use Tests\Support\Fake\InMemoryLoans;
use Tests\Support\Fake\InstantTransactions;

/** T-10 · HU-07: devolver suma un ejemplar y devolver dos veces no suma dos. */
final class ReturnLoanItemServiceTest extends TestCase
{
    private InMemoryBooks $books;

    private InMemoryLoans $loans;

    private ReturnLoanItemService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBooks();
        $this->loans = new InMemoryLoans();

        $book = Book::register(
            'b-1',
            'Cien años de soledad',
            'Gabriel García Márquez',
            Isbn::fromString('9780307474728'),
            'cat-1',
            Quantity::fromInt(2),
        );

        // El libro ya salió prestado: queda un ejemplar en el estante.
        $this->books->seed($book->withdraw());

        $this->loans->add(
            Loan::open('l-1', 'm-1', 'u-1', new DateTimeImmutable('2026-10-14T14:15:00Z'), new LoanPolicy())
                ->addItem('i-1', 'b-1', 'Cien años de soledad', Isbn::fromString('9780307474728'), 'Novela'),
        );

        $this->service = new ReturnLoanItemService(
            $this->loans,
            $this->books,
            new InstantTransactions(),
            new FixedClock(new DateTimeImmutable('2026-10-20T09:00:00Z')),
        );
    }

    public function test_devolver_pone_la_fecha_y_suma_un_ejemplar(): void
    {
        $loan = $this->service->handle('l-1', 'i-1');

        $this->assertNotNull($loan->item('i-1')->returnedAt);
        $this->assertSame(2, $this->books->stored('b-1')->availableCopies);
    }

    public function test_al_devolver_todo_el_prestamo_queda_cerrado(): void
    {
        $loan = $this->service->handle('l-1', 'i-1');

        $this->assertSame(LoanStatus::Closed, $loan->statusAt(new DateTimeImmutable('2026-10-21T09:00:00Z')));
    }

    public function test_devolver_dos_veces_no_suma_dos_veces(): void
    {
        $this->service->handle('l-1', 'i-1');

        try {
            $this->service->handle('l-1', 'i-1');
            $this->fail('Se esperaba ItemAlreadyReturned.');
        } catch (ItemAlreadyReturned) {
            $this->assertSame(2, $this->books->stored('b-1')->availableCopies);
        }
    }

    public function test_una_linea_de_otro_prestamo_no_existe_aqui(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('l-1', 'i-404');
    }

    public function test_un_prestamo_inexistente_es_un_cuatrocientos_cuatro(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('l-404', 'i-1');
    }
}
