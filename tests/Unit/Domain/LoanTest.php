<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Book;
use App\Domain\Entities\Loan;
use App\Domain\Entities\LoanStatus;
use App\Domain\Errors\BookNotAvailable;
use App\Domain\Errors\DuplicateBookInLoan;
use App\Domain\Errors\InvalidValue;
use App\Domain\Errors\ItemAlreadyReturned;
use App\Domain\Errors\LoanItemNotFound;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedClock;

class LoanTest extends TestCase
{
    private FixedClock $clock;

    protected function setUp(): void
    {
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-14T14:15:00Z'));
    }

    private function makeBook(string $id, int $totalCopies = 1): Book
    {
        return Book::create($id, "Libro {$id}", 'Autor', Isbn::fromString('9780307474728'), 'category', Quantity::of($totalCopies));
    }

    private function startLoan(): Loan
    {
        return Loan::start('loan-1', 'member-1', 'user-1', $this->clock->now());
    }

    public function test_item_is_due_fourteen_days_after_loan_and_freezes_book_data(): void
    {
        $book = $this->makeBook('book-1');
        $item = $this->startLoan()->addItem('item-1', $book, 'Novela');

        $this->assertEquals(new DateTimeImmutable('2026-10-28T14:15:00Z'), $item->dueAt);
        $this->assertSame('Libro book-1', $item->bookTitle);
        $this->assertSame('Novela', $item->categoryName);
        $this->assertSame(0, $book->availableCopies());
    }

    public function test_status_is_open_then_overdue_then_closed(): void
    {
        $book = $this->makeBook('book-1');
        $loan = $this->startLoan();
        $loan->addItem('item-1', $book, 'Novela');

        $this->assertSame(LoanStatus::Open, $loan->statusAt($this->clock->now()));

        $this->clock->advance('+15 days');
        $this->assertSame(LoanStatus::Overdue, $loan->statusAt($this->clock->now()));
        $this->assertSame(1, $loan->items()[0]->daysLateAt($this->clock->now()));

        $loan->returnItem('item-1', $book, $this->clock->now());
        $this->assertSame(LoanStatus::Closed, $loan->statusAt($this->clock->now()));
        $this->assertSame(1, $book->availableCopies());
    }

    public function test_rejects_more_than_three_items(): void
    {
        $loan = $this->startLoan();
        $loan->addItem('item-1', $this->makeBook('book-1'), 'Novela');
        $loan->addItem('item-2', $this->makeBook('book-2'), 'Novela');
        $loan->addItem('item-3', $this->makeBook('book-3'), 'Novela');

        $this->expectException(InvalidValue::class);
        $loan->addItem('item-4', $this->makeBook('book-4'), 'Novela');
    }

    public function test_rejects_same_book_twice(): void
    {
        $book = $this->makeBook('book-1', 2);
        $loan = $this->startLoan();
        $loan->addItem('item-1', $book, 'Novela');

        $this->expectException(DuplicateBookInLoan::class);
        $loan->addItem('item-2', $book, 'Novela');
    }

    public function test_book_without_copies_is_not_lent(): void
    {
        $book = $this->makeBook('book-1');
        $book->withdraw();

        $this->expectException(BookNotAvailable::class);
        $this->startLoan()->addItem('item-1', $book, 'Novela');
    }

    public function test_returning_twice_never_restocks_twice(): void
    {
        $book = $this->makeBook('book-1');
        $loan = $this->startLoan();
        $loan->addItem('item-1', $book, 'Novela');
        $loan->returnItem('item-1', $book, $this->clock->now());

        $this->expectException(ItemAlreadyReturned::class);
        try {
            $loan->returnItem('item-1', $book, $this->clock->now());
        } finally {
            $this->assertSame(1, $book->availableCopies());
        }
    }

    public function test_returning_unknown_item_fails(): void
    {
        $this->expectException(LoanItemNotFound::class);
        $this->startLoan()->returnItem('missing', $this->makeBook('book-1'), $this->clock->now());
    }

    public function test_loan_without_items_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);
        $this->startLoan()->ensureHasItems();
    }
}
