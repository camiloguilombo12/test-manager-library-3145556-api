<?php

namespace Tests\Unit\Application;

use App\Application\Errors\ResourceNotFound;
use App\Application\Services\LendBooksService;
use App\Application\Services\ReturnItemService;
use App\Domain\Entities\Book;
use App\Domain\Entities\Loan;
use App\Domain\Entities\LoanStatus;
use App\Domain\Entities\Member;
use App\Domain\Errors\ItemAlreadyReturned;
use App\Domain\Errors\LoanItemNotFound;
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

class ReturnItemServiceTest extends TestCase
{
    private const NOVEL = '11111111-1111-4111-8111-111111111111';

    private InMemoryBookRepository $books;
    private FixedClock $clock;
    private ReturnItemService $service;
    private Loan $loan;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $loans = new InMemoryLoanRepository();
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-14T14:15:00Z'));

        $members = new InMemoryMemberRepository();
        $members->add(Member::create('member-1', 'Ana Pérez', '1020304050'));
        $this->books->save(Book::create('book-1', 'Libro 1', 'Autor', Isbn::fromString('9780307474728'), self::NOVEL, Quantity::of(1)));
        $this->books->save(Book::create('book-2', 'Libro 2', 'Autor', Isbn::fromString('9780307474728'), self::NOVEL, Quantity::of(1)));

        $lend = new LendBooksService($members, $this->books, new InMemoryCategoryRepository([self::NOVEL => 'Novela']), $loans, new SequentialIdGenerator(), $this->clock);
        $this->loan = $lend->lend('member-1', 'user-1', ['book-1', 'book-2']);

        $this->service = new ReturnItemService($loans, $this->books, $this->clock);
    }

    public function test_return_sets_returned_at_and_restocks(): void
    {
        $itemId = $this->loan->items()[0]->id;

        $item = $this->service->returnItem($this->loan->id, $itemId);

        $this->assertEquals($this->clock->now(), $item->returnedAt());
        $this->assertSame(1, $this->books->findActiveById('book-1')->availableCopies());
    }

    public function test_loan_is_closed_when_every_item_is_returned(): void
    {
        foreach ($this->loan->items() as $item) {
            $this->service->returnItem($this->loan->id, $item->id);
        }

        $this->assertSame(LoanStatus::Closed, $this->loan->statusAt($this->clock->now()));
    }

    public function test_returning_twice_never_adds_twice(): void
    {
        $itemId = $this->loan->items()[0]->id;
        $this->service->returnItem($this->loan->id, $itemId);

        try {
            $this->service->returnItem($this->loan->id, $itemId);
            $this->fail('Se esperaba ItemAlreadyReturned');
        } catch (ItemAlreadyReturned) {
            $this->assertSame(1, $this->books->findActiveById('book-1')->availableCopies());
        }
    }

    public function test_item_of_another_loan_is_not_found(): void
    {
        $this->expectException(LoanItemNotFound::class);
        $this->service->returnItem($this->loan->id, 'item-from-other-loan');
    }

    public function test_unknown_loan_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);
        $this->service->returnItem('missing', 'any-item');
    }
}
