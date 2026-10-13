<?php

namespace Tests\Fakes;

use App\Application\Ports\LoanRepository;
use App\Domain\Entities\Loan;
use DateTimeImmutable;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var Loan[] */
    public array $loans = [];

    public function findById(string $id): ?Loan
    {
        return $this->loans[$id] ?? null;
    }

    public function countUnreturnedItems(string $memberId): int
    {
        $count = 0;

        foreach ($this->loansOf($memberId) as $loan) {
            foreach ($loan->items() as $item) {
                $count += $item->isReturned() ? 0 : 1;
            }
        }

        return $count;
    }

    public function memberHasOverdueItems(string $memberId, DateTimeImmutable $now): bool
    {
        foreach ($this->loansOf($memberId) as $loan) {
            foreach ($loan->items() as $item) {
                if ($item->isOverdueAt($now)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function save(Loan $loan, array $books): void
    {
        $this->loans[$loan->id] = $loan;
    }

    /**
     * @return Loan[]
     */
    private function loansOf(string $memberId): array
    {
        return array_filter($this->loans, fn (Loan $loan) => $loan->memberId === $memberId);
    }
}
