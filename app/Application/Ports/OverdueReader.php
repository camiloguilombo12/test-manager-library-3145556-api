<?php

namespace App\Application\Ports;

use DateTimeImmutable;

/**
 * Puerto de lectura de líneas vencidas (Q8). El adaptador usa el índice parcial de loan_item (due_at) sin devolver.
 */
interface OverdueReader
{
    /**
     * @return OverdueLine[] líneas sin devolver con due_at anterior a $now
     */
    public function findOverdueLines(DateTimeImmutable $now): array;
}
