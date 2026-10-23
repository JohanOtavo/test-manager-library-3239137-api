<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanItem;
use DateTimeImmutable;

/** Forma del préstamo en la respuesta (api-contract §3.6). */
final class LoanPresenter
{
    /** @return array<string, mixed> */
    public static function one(Loan $loan, DateTimeImmutable $now): array
    {
        return [
            'id' => $loan->id,
            'member_id' => $loan->memberId,
            'loaned_at' => Instant::format($loan->loanedAt),
            // El estado se calcula en cada lectura (CA-08.3).
            'status' => $loan->statusAt($now)->value,
            'items' => array_map(fn (LoanItem $item): array => self::item($item), $loan->items),
        ];
    }

    /** @return array<string, mixed> */
    public static function item(LoanItem $item): array
    {
        return [
            'id' => $item->id,
            'book_id' => $item->bookId,
            'book_title' => $item->bookTitle,
            'isbn' => $item->isbn->value,
            'category_name' => $item->categoryName,
            'due_at' => Instant::format($item->dueAt),
            'returned_at' => Instant::formatOrNull($item->returnedAt),
        ];
    }

    /**
     * @param  list<Loan>  $loans
     * @return list<array<string, mixed>>
     */
    public static function many(array $loans, DateTimeImmutable $now): array
    {
        return array_map(fn (Loan $loan): array => self::one($loan, $now), $loans);
    }
}
