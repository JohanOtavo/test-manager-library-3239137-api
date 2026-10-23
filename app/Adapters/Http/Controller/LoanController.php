<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\CurrentUser;
use App\Adapters\Http\Presenter\Instant;
use App\Adapters\Http\Presenter\LoanPresenter;
use App\Adapters\Http\Presenter\PagePresenter;
use App\Application\Port\Clock;
use App\Application\Shared\OverdueRow;
use App\Application\Shared\Pagination;
use App\Application\UseCase\Loan\GetLoanService;
use App\Application\UseCase\Loan\LendBooksService;
use App\Application\UseCase\Loan\ListOverdueService;
use App\Application\UseCase\Loan\ReturnLoanItemService;
use App\Application\UseCase\Loan\SearchLoansService;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\LoanStatus;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-06 a HU-09 · /loans. */
final readonly class LoanController
{
    public function __construct(
        private LendBooksService $lendBooks,
        private ReturnLoanItemService $returnItem,
        private GetLoanService $getLoan,
        private SearchLoansService $searchLoans,
        private ListOverdueService $listOverdue,
        private Clock $clock,
        private int $pageSizeMax,
        private int $pageSizeDefault,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'member_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'uuid'],
        ]);

        $bookIds = array_map(fn (array $item): string => $item['book_id'], $input['items']);

        $loan = $this->lendBooks->handle(
            $input['member_id'],
            $bookIds,
            CurrentUser::of($request)->id,
        );

        return new JsonResponse(LoanPresenter::one($loan, $this->clock->now()), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $page = Pagination::of(
            $request->integer('page', 1),
            $request->integer('size', $this->pageSizeDefault),
            $this->pageSizeMax,
            $this->pageSizeDefault,
        );

        $result = $this->searchLoans->handle(
            $this->instantOrNull($request->query('from'), 'from'),
            $this->instantOrNull($request->query('to'), 'to'),
            $this->statusOrNull($request->query('status')),
            $request->query('member_id'),
            $page,
        );

        return new JsonResponse(PagePresenter::of(
            $result,
            LoanPresenter::many($result->items, $this->clock->now()),
        ));
    }

    public function show(string $loan): JsonResponse
    {
        return new JsonResponse(LoanPresenter::one($this->getLoan->handle($loan), $this->clock->now()));
    }

    public function returnLoanItem(string $loan, string $item): JsonResponse
    {
        $updated = $this->returnItem->handle($loan, $item);

        return new JsonResponse(LoanPresenter::item($updated->item($item)));
    }

    public function overdue(): JsonResponse
    {
        $items = array_map(fn (OverdueRow $row): array => [
            'loan_id' => $row->loanId,
            'item_id' => $row->itemId,
            'book_title' => $row->bookTitle,
            'member_name' => $row->memberName,
            'due_at' => Instant::format($row->dueAt),
            'days_late' => $row->daysLate,
        ], $this->listOverdue->handle());

        // Más días de retraso primero (CA-09.2).
        usort($items, fn (array $left, array $right): int => $right['days_late'] <=> $left['days_late']);

        return new JsonResponse(['items' => $items]);
    }

    private function instantOrNull(mixed $value, string $field): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = date_create_immutable((string) $value);

        if ($parsed === false) {
            throw new ValidationFailed('La fecha no tiene un formato válido.', [
                ['field' => $field, 'issue' => 'fecha inválida'],
            ]);
        }

        return $parsed;
    }

    private function statusOrNull(mixed $value): ?LoanStatus
    {
        if ($value === null || $value === '') {
            return null;
        }

        $status = LoanStatus::tryFrom((string) $value);

        if ($status === null) {
            throw new ValidationFailed('El estado debe ser open, overdue o closed.', [
                ['field' => 'status', 'issue' => 'estado desconocido'],
            ]);
        }

        return $status;
    }
}
