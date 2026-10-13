<?php

namespace App\Application\Services;

use App\Application\Errors\ResourceNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Domain\Entities\LoanItem;
use App\Domain\Errors\LoanItemNotFound;

/**
 * Caso de uso: devolver una línea de un préstamo (HU-07).
 */
final class ReturnItemService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly BookRepository $books,
        private readonly Clock $clock,
    ) {}

    public function returnItem(string $loanId, string $itemId): LoanItem
    {
        $loan = $this->loans->findById($loanId) ?? throw new ResourceNotFound('El préstamo no existe.');

        // La línea debe pertenecer a este préstamo (spec-review.md A-08)
        $bookId = null;
        foreach ($loan->items() as $item) {
            if ($item->id === $itemId) {
                $bookId = $item->bookId;
            }
        }

        if ($bookId === null) {
            throw new LoanItemNotFound($itemId);
        }

        $book = $this->books->findActiveById($bookId) ?? throw new ResourceNotFound('El libro no existe.');

        $returnedItem = $loan->returnItem($itemId, $book, $this->clock->now());
        $this->loans->save($loan, [$book]);

        return $returnedItem;
    }
}
