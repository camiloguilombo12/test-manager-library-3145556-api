<?php

namespace App\Application\Ports;

final class MostBorrowedRow
{
    public function __construct(
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $categoryName,
        public readonly int $timesBorrowed,
    ) {}
}
