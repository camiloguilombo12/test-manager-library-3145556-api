<?php

namespace App\Application\Services;

use App\Application\Errors\MemberHasOverdue;
use App\Application\Errors\MemberLimitExceeded;
use App\Application\Errors\ResourceNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Domain\Entities\Loan;
use App\Domain\Errors\DuplicateBookInLoan;
use App\Domain\Errors\InvalidValue;

/**
 * Caso de uso: registrar un préstamo (HU-06). Presta y no hace nada más (artículo IV, SRP).
 */
final class LendBooksService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly BookRepository $books,
        private readonly CategoryRepository $categories,
        private readonly LoanRepository $loans,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {}

    /**
     * Valida en el orden de spec-review.md A-07 y responde el primer error.
     *
     * @param string[] $bookIds
     */
    public function lend(string $memberId, string $loanedByUserId, array $bookIds): Loan
    {
        $this->validateRequest($bookIds);

        if ($this->members->findById($memberId) === null) {
            throw new ResourceNotFound('El socio no existe.');
        }

        $now = $this->clock->now();

        if ($this->loans->memberHasOverdueItems($memberId, $now)) {
            throw new MemberHasOverdue();
        }

        $currentCount = $this->loans->countUnreturnedItems($memberId);

        if ($currentCount + count($bookIds) > Loan::MAX_ITEMS) {
            throw new MemberLimitExceeded($currentCount);
        }

        $books = [];
        foreach ($bookIds as $bookId) {
            $books[] = $this->books->findActiveById($bookId) ?? throw new ResourceNotFound('El libro no existe.');
        }

        $loan = Loan::start($this->ids->newId(), $memberId, $loanedByUserId, $now);
        foreach ($books as $book) {
            $loan->addItem($this->ids->newId(), $book, $this->categories->nameOf($book->categoryId()) ?? '');
        }
        $loan->ensureHasItems();

        $this->loans->save($loan, $books);

        return $loan;
    }

    /**
     * @param string[] $bookIds
     */
    private function validateRequest(array $bookIds): void
    {
        if ($bookIds === [] || count($bookIds) > Loan::MAX_ITEMS) {
            throw new InvalidValue('items', 'Un préstamo lleva de 1 a 3 libros.');
        }

        if (count(array_unique($bookIds)) !== count($bookIds)) {
            throw new DuplicateBookInLoan((string) array_values(array_diff_assoc($bookIds, array_unique($bookIds)))[0]);
        }
    }
}
