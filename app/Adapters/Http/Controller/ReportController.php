<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Application\Shared\MostBorrowedRow;
use App\Application\UseCase\Report\MostBorrowedReportService;
use App\Domain\Exception\ValidationFailed;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-10 · GET /reports/most-borrowed. Solo admin (CA-10.5). */
final readonly class ReportController
{
    public function __construct(private MostBorrowedReportService $mostBorrowed) {}

    public function mostBorrowed(Request $request): JsonResponse
    {
        $input = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        $from = $this->day($input['from'], 'from');
        $to = $this->day($input['to'], 'to');

        $rows = array_map(fn (MostBorrowedRow $row): array => [
            'book_id' => $row->bookId,
            'book_title' => $row->bookTitle,
            'category_name' => $row->categoryName,
            'times_borrowed' => $row->timesBorrowed,
        ], $this->mostBorrowed->handle($from, $to));

        return new JsonResponse([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    /** El rango va por días completos y los dos extremos cuentan (CA-10.1). */
    private function day(string $value, string $field): DateTimeImmutable
    {
        $parsed = date_create_immutable($value.' 00:00:00 UTC');

        if ($parsed === false) {
            throw new ValidationFailed('La fecha no tiene un formato válido.', [
                ['field' => $field, 'issue' => 'fecha inválida'],
            ]);
        }

        return $parsed;
    }
}
