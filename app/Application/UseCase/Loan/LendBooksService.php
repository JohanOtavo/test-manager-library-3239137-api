<?php

declare(strict_types=1);

namespace App\Application\UseCase\Loan;

use App\Application\Port\BookRepository;
use App\Application\Port\CategoryRepository;
use App\Application\Port\Clock;
use App\Application\Port\IdGenerator;
use App\Application\Port\LoanRepository;
use App\Application\Port\MemberRepository;
use App\Application\Port\TransactionManager;
use App\Domain\Exception\BookNotAvailable;
use App\Domain\Exception\DuplicateBookInLoan;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Loan\Loan;
use App\Domain\Loan\MemberBorrowingLimit;
use App\Domain\Shared\LoanPolicy;

/**
 * HU-06 · Registrar un préstamo.
 *
 * Todo ocurre dentro de una transacción: descontar el ejemplar y crear la línea
 * son la misma operación, y si algo falla no queda nada escrito (CA-06.3).
 */
final readonly class LendBooksService
{
    public function __construct(
        private LoanRepository $loans,
        private BookRepository $books,
        private MemberRepository $members,
        private CategoryRepository $categories,
        private TransactionManager $transactions,
        private Clock $clock,
        private IdGenerator $ids,
        private LoanPolicy $policy,
        private MemberBorrowingLimit $limit,
    ) {}

    /** @param list<string> $bookIds */
    public function handle(string $memberId, array $bookIds, string $actorUserId): Loan
    {
        // Se avisa del libro repetido antes de tocar el inventario (CA-06.2).
        if (count(array_unique($bookIds)) !== count($bookIds)) {
            throw new DuplicateBookInLoan('El mismo libro no puede repetirse en un préstamo.', [
                ['field' => 'items', 'issue' => 'libro repetido'],
            ]);
        }

        return $this->transactions->transactional(
            fn (): Loan => $this->register($memberId, $bookIds, $actorUserId),
        );
    }

    /** @param list<string> $bookIds */
    private function register(string $memberId, array $bookIds, string $actorUserId): Loan
    {
        if ($this->members->find($memberId) === null) {
            throw new ResourceNotFound('Ese socio no existe.');
        }

        $now = $this->clock->now();

        $this->limit->ensureCanBorrow(
            $this->members->countOpenBooks($memberId),
            count($bookIds),
            $this->members->hasOverdue($memberId, $now),
        );

        $loan = Loan::open($this->ids->next(), $memberId, $actorUserId, $now, $this->policy);

        foreach ($bookIds as $bookId) {
            $loan = $this->lendOne($loan, $bookId);
        }

        $loan->ensureReadyToRegister();
        $this->loans->add($loan);

        return $loan;
    }

    private function lendOne(Loan $loan, string $bookId): Loan
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFound('Uno de los libros no existe o está dado de baja.');
        }

        $category = $this->categories->find($book->categoryId);

        if ($category === null) {
            throw new ResourceNotFound('La categoría del libro no existe.');
        }

        // El dominio decide si queda ejemplar; la escritura condicionada decide
        // quién gana cuando dos bibliotecarios piden el último a la vez (ADR-002).
        $withdrawn = $book->withdraw();

        if (! $this->books->applyWithdrawal($withdrawn, $book->availableCopies)) {
            throw new BookNotAvailable(sprintf('No quedan ejemplares disponibles de "%s".', $book->title));
        }

        return $loan->addItem(
            $this->ids->next(),
            $book->id,
            $book->title,
            $book->isbn,
            $category->name,
        );
    }
}
