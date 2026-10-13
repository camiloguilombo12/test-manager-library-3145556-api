<?php

namespace App\Application\Ports;

use App\Domain\ValueObjects\DateRange;

/**
 * Puerto de lectura del reporte (ADR-004): una sola consulta agregada que agrupa por los valores congelados
 * de loan_item (book_id, book_title, category_name), nunca por el catálogo vivo.
 */
interface ReportReader
{
    /**
     * @return MostBorrowedRow[] filas con loaned_at >= from y loaned_at < (to + 1 día)
     */
    public function mostBorrowed(DateRange $range): array;
}
