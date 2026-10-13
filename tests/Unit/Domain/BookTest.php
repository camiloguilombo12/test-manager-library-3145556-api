<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Book;
use App\Domain\Errors\BookNotAvailable;
use App\Domain\Errors\InvalidValue;
use App\Domain\Errors\StockOutOfRange;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    private function makeBook(int $totalCopies = 2): Book
    {
        return Book::create(
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            ' Cien años de soledad ',
            'Gabriel García Márquez',
            Isbn::fromString('9780307474728'),
            '11111111-1111-4111-8111-111111111111',
            Quantity::of($totalCopies),
        );
    }

    public function test_new_book_starts_with_all_copies_available(): void
    {
        $book = $this->makeBook(4);

        $this->assertSame(4, $book->availableCopies());
        $this->assertSame('Cien años de soledad', $book->title());
        $this->assertSame(1, $book->version());
        $this->assertNull($book->coverKey());
    }

    public function test_withdraw_never_goes_below_zero(): void
    {
        $book = $this->makeBook(1);
        $book->withdraw();

        $this->assertSame(0, $book->availableCopies());
        $this->expectException(BookNotAvailable::class);
        $book->withdraw();
    }

    public function test_restock_never_goes_above_total(): void
    {
        $book = $this->makeBook(1);
        $book->withdraw();
        $book->restock();

        $this->assertSame(1, $book->availableCopies());
        $this->expectException(StockOutOfRange::class);
        $book->restock();
    }

    public function test_changing_total_moves_available_by_same_difference(): void
    {
        $book = $this->makeBook(4);
        $book->withdraw();
        $book->changeTotalCopies(Quantity::of(6));

        $this->assertSame(6, $book->totalCopies());
        $this->assertSame(5, $book->availableCopies());
    }

    public function test_changing_total_below_lent_copies_is_rejected(): void
    {
        $book = $this->makeBook(4);
        $book->withdraw();
        $book->withdraw();
        $book->withdraw();

        $this->expectException(InvalidValue::class);
        $book->changeTotalCopies(Quantity::of(2));
    }

    public function test_empty_title_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);
        Book::create('id', '   ', 'Autor', Isbn::fromString('9780307474728'), 'category', Quantity::of(1));
    }

    public function test_mark_as_deleted_keeps_first_date(): void
    {
        $book = $this->makeBook();
        $book->markAsDeleted(new DateTimeImmutable('2026-10-13T10:00:00Z'));
        $book->markAsDeleted(new DateTimeImmutable('2026-10-14T10:00:00Z'));

        $this->assertFalse($book->isActive());
        $this->assertEquals(new DateTimeImmutable('2026-10-13T10:00:00Z'), $book->deletedAt());
    }
}
