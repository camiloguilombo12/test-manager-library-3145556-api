<?php

namespace App\Domain\Entities;

use App\Domain\Errors\ItemAlreadyReturned;
use DateTimeImmutable;

/**
 * Un libro dentro de un préstamo. Título, ISBN y categoría quedan congelados en el instante del préstamo (artículo VIII).
 */
final class LoanItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly DateTimeImmutable $dueAt,
        private ?DateTimeImmutable $returnedAt = null,
    ) {}

    public function markAsReturned(DateTimeImmutable $at): void
    {
        if ($this->returnedAt !== null) {
            throw new ItemAlreadyReturned($this->id);
        }

        $this->returnedAt = $at;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    /**
     * Vencida: sin devolver y con due_at anterior a ahora (CA-09.1).
     */
    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return ! $this->isReturned() && $this->dueAt < $now;
    }

    /**
     * Días completos de retraso, mínimo 1 si está vencida (spec-review.md A-04).
     */
    public function daysLateAt(DateTimeImmutable $now): int
    {
        if (! $this->isOverdueAt($now)) {
            return 0;
        }

        $seconds = $now->getTimestamp() - $this->dueAt->getTimestamp();

        return max(1, intdiv($seconds, 86400));
    }

    public function returnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }
}
