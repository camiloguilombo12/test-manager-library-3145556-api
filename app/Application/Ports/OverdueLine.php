<?php

namespace App\Application\Ports;

use DateTimeImmutable;

final class OverdueLine
{
    public function __construct(
        public readonly string $loanId,
        public readonly string $itemId,
        public readonly string $bookTitle,
        public readonly string $memberName,
        public readonly DateTimeImmutable $dueAt,
    ) {}
}
