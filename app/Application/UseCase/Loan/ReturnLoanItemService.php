<?php

declare(strict_types=1);

namespace App\Application\UseCase\Loan;

use App\Application\Port\BookRepository;
use App\Application\Port\Clock;
use App\Application\Port\LoanRepository;
use App\Application\Port\TransactionManager;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Loan\Loan;

/** HU-07 · Devolver una línea: pone returned_at y suma 1 a los disponibles (CA-07.1). */
final readonly class ReturnLoanItemService
{
    public function __construct(
        private LoanRepository $loans,
        private BookRepository $books,
        private TransactionManager $transactions,
        private Clock $clock,
    ) {}

    public function handle(string $loanId, string $itemId): Loan
    {
        return $this->transactions->transactional(
            fn (): Loan => $this->returnOne($loanId, $itemId),
        );
    }

    private function returnOne(string $loanId, string $itemId): Loan
    {
        $loan = $this->loans->find($loanId);

        if ($loan === null) {
            throw new ResourceNotFound('Ese préstamo no existe.');
        }

        $item = $loan->item($itemId);
        $now = $this->clock->now();

        // La escritura condicionada es la que decide: si otra petición devolvió
        // la línea primero, aquí no se suma nada (CA-07.2).
        if ($item->isReturned() || ! $this->loans->markItemReturned($loanId, $itemId, $now)) {
            throw new ItemAlreadyReturned('Esta línea del préstamo ya estaba devuelta.');
        }

        $this->books->applyRestock($item->bookId);

        return $loan->returnItem($itemId, $now);
    }
}
