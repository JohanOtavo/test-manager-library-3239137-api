<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Loan\LendBooksService;
use App\Domain\Book\Book;
use App\Domain\Exception\BookNotAvailable;
use App\Domain\Exception\DuplicateBookInLoan;
use App\Domain\Exception\MemberHasOverdue;
use App\Domain\Exception\MemberLimitExceeded;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Loan\MemberBorrowingLimit;
use App\Domain\Member\Member;
use App\Domain\Shared\DocumentNumber;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\LoanPolicy;
use App\Domain\Shared\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;
use Tests\Support\Fake\InMemoryBooks;
use Tests\Support\Fake\InMemoryCategories;
use Tests\Support\Fake\InMemoryLoans;
use Tests\Support\Fake\InMemoryMembers;
use Tests\Support\Fake\InstantTransactions;
use Tests\Support\Fake\SequentialIds;

/** T-09 · HU-06 completa, contra dobles de prueba y sin base de datos. */
final class LendBooksServiceTest extends TestCase
{
    private const CATEGORY_ID = '11111111-1111-4111-8111-111111111111';

    private InMemoryBooks $books;

    private InMemoryMembers $members;

    private InMemoryLoans $loans;

    private LendBooksService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBooks();
        $this->members = new InMemoryMembers();
        $this->loans = new InMemoryLoans();

        $categories = new InMemoryCategories();
        $categories->add(self::CATEGORY_ID, 'Novela');

        $this->members->add(Member::register('m-1', 'Ana Pérez', DocumentNumber::fromString('1098765432')));
        $this->books->seed($this->book('b-1', 'Cien años de soledad', '9780307474728', 2));
        $this->books->seed($this->book('b-2', 'Rayuela', '9788437604572', 1));

        $policy = new LoanPolicy();

        $this->service = new LendBooksService(
            $this->loans,
            $this->books,
            $this->members,
            $categories,
            new InstantTransactions(),
            new FixedClock(new DateTimeImmutable('2026-10-14T14:15:00Z')),
            new SequentialIds(),
            $policy,
            new MemberBorrowingLimit($policy),
        );
    }

    public function test_presta_y_descuenta_un_ejemplar_por_linea(): void
    {
        $loan = $this->service->handle('m-1', ['b-1', 'b-2'], 'u-1');

        $this->assertCount(2, $loan->items);
        $this->assertSame(1, $this->books->stored('b-1')->availableCopies);
        $this->assertSame(0, $this->books->stored('b-2')->availableCopies);
        $this->assertNotNull($this->loans->find($loan->id));
    }

    public function test_congela_los_datos_del_catalogo_en_la_linea(): void
    {
        $item = $this->service->handle('m-1', ['b-1'], 'u-1')->items[0];

        $this->assertSame('Cien años de soledad', $item->bookTitle);
        $this->assertSame('Novela', $item->categoryName);
        $this->assertSame('2026-10-28T14:15:00+00:00', $item->dueAt->format('c'));
    }

    public function test_el_libro_repetido_se_rechaza_sin_tocar_el_inventario(): void
    {
        try {
            $this->service->handle('m-1', ['b-1', 'b-1'], 'u-1');
            $this->fail('Se esperaba DuplicateBookInLoan.');
        } catch (DuplicateBookInLoan) {
            $this->assertSame(2, $this->books->stored('b-1')->availableCopies);
        }
    }

    public function test_sin_ejemplares_responde_conflicto(): void
    {
        $this->books->forceAvailableCopies('b-2', 0);

        $this->expectException(BookNotAvailable::class);

        $this->service->handle('m-1', ['b-2'], 'u-1');
    }

    /** RNF-2 - Si otro se llevó el ejemplar entre la lectura y la escritura, este pierde. */
    public function test_pierde_la_carrera_por_el_ultimo_ejemplar(): void
    {
        $this->books->seed($this->book('b-3', 'El Aleph', '9789875666061', 1));
        $this->books->simulateConcurrentWithdrawalOn('b-3');

        try {
            $this->service->handle('m-1', ['b-3'], 'u-1');
            $this->fail('Se esperaba BookNotAvailable.');
        } catch (BookNotAvailable) {
            // El que ganó se llevó el único ejemplar; este no sumó ni restó nada.
            $this->assertSame(0, $this->books->stored('b-3')->availableCopies);
        }
    }

    public function test_el_socio_no_pasa_de_tres_libros_sin_devolver(): void
    {
        $this->members->withOpenBooks(2);

        $this->expectException(MemberLimitExceeded::class);

        $this->service->handle('m-1', ['b-1', 'b-2'], 'u-1');
    }

    public function test_el_socio_con_vencidos_no_pide_mas(): void
    {
        $this->members->withOverdue(true);

        $this->expectException(MemberHasOverdue::class);

        $this->service->handle('m-1', ['b-1'], 'u-1');
    }

    public function test_un_socio_inexistente_es_un_cuatrocientos_cuatro(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('m-404', ['b-1'], 'u-1');
    }

    private function book(string $id, string $title, string $isbn, int $copies): Book
    {
        return Book::register(
            $id,
            $title,
            'Autor de prueba',
            Isbn::fromString($isbn),
            self::CATEGORY_ID,
            Quantity::fromInt($copies),
        );
    }
}
