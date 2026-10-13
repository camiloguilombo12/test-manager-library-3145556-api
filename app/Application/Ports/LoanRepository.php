<?php

namespace App\Application\Ports;

use App\Domain\Entities\Book;
use App\Domain\Entities\Loan;
use DateTimeImmutable;

interface LoanRepository
{
    public function countUnreturnedItems(string $memberId): int;

    public function memberHasOverdueItems(string $memberId, DateTimeImmutable $now): bool;

    /**
     * Guarda el préstamo y los libros tocados en una sola transacción (architecture.md §4).
     *
     * @param Book[] $books
     */
    public function save(Loan $loan, array $books): void;
}
