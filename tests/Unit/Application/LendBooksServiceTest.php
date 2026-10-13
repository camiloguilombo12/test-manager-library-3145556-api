<?php

namespace Tests\Unit\Application;

use App\Application\Errors\MemberHasOverdue;
use App\Application\Errors\MemberLimitExceeded;
use App\Application\Errors\ResourceNotFound;
use App\Application\Services\LendBooksService;
use App\Domain\Entities\Book;
use App\Domain\Entities\LoanStatus;
use App\Domain\Entities\Member;
use App\Domain\Errors\BookNotAvailable;
use App\Domain\Errors\DuplicateBookInLoan;
use App\Domain\Errors\InvalidValue;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedClock;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;
use Tests\Fakes\SequentialIdGenerator;

class LendBooksServiceTest extends TestCase
{
    private const NOVEL = '11111111-1111-4111-8111-111111111111';

    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private FixedClock $clock;
    private LendBooksService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-14T14:15:00Z'));

        $members = new InMemoryMemberRepository();
        $members->add(Member::create('member-1', 'Ana Pérez', '1020304050'));

        foreach (['book-1', 'book-2', 'book-3', 'book-4'] as $bookId) {
            $this->books->save(Book::create($bookId, "Libro {$bookId}", 'Autor', Isbn::fromString('9780307474728'), self::NOVEL, Quantity::of(1)));
        }

        $this->service = new LendBooksService(
            $members,
            $this->books,
            new InMemoryCategoryRepository([self::NOVEL => 'Novela']),
            $this->loans,
            new SequentialIdGenerator(),
            $this->clock,
        );
    }

    public function test_lends_books_and_freezes_category_name(): void
    {
        $loan = $this->service->lend('member-1', 'user-1', ['book-1', 'book-2']);

        $this->assertCount(2, $loan->items());
        $this->assertSame('Novela', $loan->items()[0]->categoryName);
        $this->assertSame(LoanStatus::Open, $loan->statusAt($this->clock->now()));
        $this->assertSame(0, $this->books->findActiveById('book-1')->availableCopies());
    }

    public function test_repeated_book_in_request_is_rejected(): void
    {
        $this->expectException(DuplicateBookInLoan::class);
        $this->service->lend('member-1', 'user-1', ['book-1', 'book-1']);
    }

    public function test_more_than_three_lines_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);
        $this->service->lend('member-1', 'user-1', ['book-1', 'book-2', 'book-3', 'book-4']);
    }

    public function test_member_cannot_exceed_three_unreturned_books(): void
    {
        $this->service->lend('member-1', 'user-1', ['book-1', 'book-2']);

        try {
            $this->service->lend('member-1', 'user-1', ['book-3', 'book-4']);
            $this->fail('Se esperaba MemberLimitExceeded');
        } catch (MemberLimitExceeded $error) {
            $this->assertSame(2, $error->currentCount);
        }
    }

    public function test_member_with_overdue_book_cannot_borrow(): void
    {
        $this->service->lend('member-1', 'user-1', ['book-1']);
        $this->clock->advance('+15 days');

        $this->expectException(MemberHasOverdue::class);
        $this->service->lend('member-1', 'user-1', ['book-2']);
    }

    public function test_unknown_member_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);
        $this->service->lend('missing', 'user-1', ['book-1']);
    }

    public function test_book_without_copies_is_rejected_and_nothing_is_saved(): void
    {
        $this->books->findActiveById('book-1')->withdraw();

        try {
            $this->service->lend('member-1', 'user-1', ['book-1']);
            $this->fail('Se esperaba BookNotAvailable');
        } catch (BookNotAvailable) {
            $this->assertSame([], $this->loans->loans);
        }
    }
}
