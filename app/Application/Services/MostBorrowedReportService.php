<?php

namespace App\Application\Services;

use App\Application\Errors\InvalidDateRange;
use App\Application\Ports\MostBorrowedRow;
use App\Application\Ports\ReportReader;
use App\Domain\Errors\InvalidValue;
use App\Domain\ValueObjects\DateRange;

/**
 * Caso de uso: reporte de libros más prestados (HU-10). No incluye socios ni bibliotecarios (CA-10.3).
 */
final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $reader) {}

    /**
     * @return array{from: string, to: string, rows: array<int, array{book_id: string, book_title: string, category_name: string, times_borrowed: int}>}
     */
    public function generate(string $from, string $to): array
    {
        try {
            $range = DateRange::fromDates($from, $to);
        } catch (InvalidValue $error) {
            // Fin anterior al inicio o fecha mal escrita: 422 INVALID_DATE_RANGE (CA-10.2)
            throw new InvalidDateRange($error->getMessage());
        }

        $rows = $this->reader->mostBorrowed($range);

        // Mayor a menor y, en empate, por título (api-contract.md §3.7)
        usort($rows, fn (MostBorrowedRow $a, MostBorrowedRow $b) => [$b->timesBorrowed, $a->bookTitle] <=> [$a->timesBorrowed, $b->bookTitle]);

        return [
            'from' => $from,
            'to' => $to,
            'rows' => array_map(fn (MostBorrowedRow $row) => [
                'book_id' => $row->bookId,
                'book_title' => $row->bookTitle,
                'category_name' => $row->categoryName,
                'times_borrowed' => $row->timesBorrowed,
            ], $rows),
        ];
    }
}
