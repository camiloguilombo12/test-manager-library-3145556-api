<?php

namespace Tests\Unit\Application;

use App\Application\Ports\MostBorrowedRow;
use App\Application\Ports\ReportReader;
use App\Application\Services\MostBorrowedReportService;
use App\Application\Errors\InvalidDateRange;
use App\Domain\ValueObjects\DateRange;
use PHPUnit\Framework\TestCase;

class MostBorrowedReportServiceTest extends TestCase
{
    private function serviceWith(array $rows): MostBorrowedReportService
    {
        $reader = new class($rows) implements ReportReader
        {
            public function __construct(private readonly array $rows) {}

            public function mostBorrowed(DateRange $range): array
            {
                return $this->rows;
            }
        };

        return new MostBorrowedReportService($reader);
    }

    public function test_orders_by_times_borrowed_then_title_and_keeps_frozen_rows_apart(): void
    {
        $report = $this->serviceWith([
            new MostBorrowedRow('book-2', 'Zorro', 'Infantil', 3),
            new MostBorrowedRow('book-1', 'Cien años de soledad', 'Novela', 12),
            // Mismo libro con categoría distinta dentro del rango: dos filas, sin elegir ganador (ADR-004)
            new MostBorrowedRow('book-1', 'Cien años de soledad', 'Historia', 3),
        ])->generate('2026-10-01', '2026-10-31');

        $this->assertSame([12, 3, 3], array_column($report['rows'], 'times_borrowed'));
        $this->assertSame(['Novela', 'Historia', 'Infantil'], array_column($report['rows'], 'category_name'));
        $this->assertSame('2026-10-01', $report['from']);
    }

    public function test_empty_range_returns_empty_list(): void
    {
        $this->assertSame([], $this->serviceWith([])->generate('2026-10-01', '2026-10-31')['rows']);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $this->expectException(InvalidDateRange::class);
        $this->serviceWith([])->generate('2026-10-31', '2026-10-01');
    }
}
