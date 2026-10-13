<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\OverdueReader;

/**
 * Caso de uso: listar libros vencidos (HU-09).
 */
final class OverdueService
{
    public function __construct(
        private readonly OverdueReader $reader,
        private readonly Clock $clock,
    ) {}

    /**
     * Ordenadas por más días de retraso primero (CA-09.2).
     *
     * @return array<int, array{loan_id: string, item_id: string, book_title: string, member_name: string, due_at: string, days_late: int}>
     */
    public function list(): array
    {
        $now = $this->clock->now();
        $rows = [];

        foreach ($this->reader->findOverdueLines($now) as $line) {
            $secondsLate = $now->getTimestamp() - $line->dueAt->getTimestamp();

            $rows[] = [
                'loan_id' => $line->loanId,
                'item_id' => $line->itemId,
                'book_title' => $line->bookTitle,
                'member_name' => $line->memberName,
                'due_at' => $line->dueAt->format('Y-m-d\TH:i:s\Z'),
                // Días completos de retraso, mínimo 1 (spec-review.md A-04)
                'days_late' => max(1, intdiv($secondsLate, 86400)),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['days_late'] <=> $a['days_late']);

        return $rows;
    }
}
