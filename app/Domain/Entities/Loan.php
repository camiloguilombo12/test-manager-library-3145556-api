<?php

namespace App\Domain\Entities;

use App\Domain\Errors\DuplicateBookInLoan;
use App\Domain\Errors\InvalidValue;
use App\Domain\Errors\LoanItemNotFound;
use DateTimeImmutable;

/**
 * Raíz del agregado préstamo. Defiende: 1 a 3 líneas, sin libro repetido, vencimiento a 14 días
 * e inmutable salvo la devolución de cada línea (architecture.md §3).
 */
final class Loan
{
    public const MAX_ITEMS = 3;
    public const LOAN_DAYS = 14;

    /** @var LoanItem[] */
    private array $items = [];

    private function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly DateTimeImmutable $loanedAt,
    ) {}

    public static function start(string $id, string $memberId, string $loanedByUserId, DateTimeImmutable $loanedAt): self
    {
        return new self($id, $memberId, $loanedByUserId, $loanedAt);
    }

    /**
     * Reconstruye un préstamo guardado con sus líneas.
     *
     * @param LoanItem[] $items
     */
    public static function restore(string $id, string $memberId, string $loanedByUserId, DateTimeImmutable $loanedAt, array $items): self
    {
        $loan = new self($id, $memberId, $loanedByUserId, $loanedAt);
        $loan->items = array_values($items);

        return $loan;
    }

    /**
     * Agrega una línea: descontar el ejemplar y crear la línea son una sola operación (architecture.md §3).
     */
    public function addItem(string $itemId, Book $book, string $categoryName): LoanItem
    {
        if (count($this->items) >= self::MAX_ITEMS) {
            throw new InvalidValue('items', 'Un préstamo lleva de 1 a 3 libros.');
        }

        foreach ($this->items as $item) {
            if ($item->bookId === $book->id) {
                throw new DuplicateBookInLoan($book->id);
            }
        }

        $book->withdraw();

        $item = new LoanItem(
            $itemId,
            $book->id,
            $book->title(),
            $book->isbn->value,
            $categoryName,
            $this->loanedAt->modify('+'.self::LOAN_DAYS.' days'),
        );
        $this->items[] = $item;

        return $item;
    }

    /**
     * Comprueba que el préstamo tenga al menos una línea antes de guardarlo (CA-06.1).
     */
    public function ensureHasItems(): void
    {
        if ($this->items === []) {
            throw new InvalidValue('items', 'Un préstamo lleva de 1 a 3 libros.');
        }
    }

    /**
     * Devuelve una línea y repone el ejemplar una sola vez (CA-07.1, CA-07.2).
     */
    public function returnItem(string $itemId, Book $book, DateTimeImmutable $at): LoanItem
    {
        $item = $this->findItem($itemId);
        $item->markAsReturned($at);
        $book->restock();

        return $item;
    }

    public function statusAt(DateTimeImmutable $now): LoanStatus
    {
        $hasPending = false;

        foreach ($this->items as $item) {
            if ($item->isOverdueAt($now)) {
                return LoanStatus::Overdue;
            }

            $hasPending = $hasPending || ! $item->isReturned();
        }

        return $hasPending ? LoanStatus::Open : LoanStatus::Closed;
    }

    /**
     * @return LoanItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    private function findItem(string $itemId): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        throw new LoanItemNotFound($itemId);
    }
}
