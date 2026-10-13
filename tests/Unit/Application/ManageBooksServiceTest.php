<?php

namespace Tests\Unit\Application;

use App\Application\Errors\BookHasOpenLoans;
use App\Application\Errors\IsbnTaken;
use App\Application\Errors\ResourceNotFound;
use App\Application\Services\ManageBooksService;
use App\Domain\Errors\InvalidValue;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedClock;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryCategoryRepository;
use Tests\Fakes\SequentialIdGenerator;

class ManageBooksServiceTest extends TestCase
{
    private const NOVEL = '11111111-1111-4111-8111-111111111111';

    private InMemoryBookRepository $books;
    private ManageBooksService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->service = new ManageBooksService(
            $this->books,
            new InMemoryCategoryRepository([self::NOVEL]),
            new SequentialIdGenerator(),
            new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z')),
        );
    }

    public function test_creates_book_with_all_copies_available(): void
    {
        $book = $this->service->create('Cien años de soledad', 'García Márquez', '9780307474728', self::NOVEL, 4);

        $this->assertSame(4, $book->availableCopies());
        $this->assertSame($book, $this->books->findActiveById($book->id));
    }

    public function test_repeated_active_isbn_is_rejected(): void
    {
        $this->service->create('Libro A', 'Autor', '9780307474728', self::NOVEL, 1);

        $this->expectException(IsbnTaken::class);
        $this->service->create('Libro B', 'Autor', '9780307474728', self::NOVEL, 1);
    }

    public function test_isbn_of_deleted_book_can_be_registered_again(): void
    {
        $book = $this->service->create('Libro A', 'Autor', '9780307474728', self::NOVEL, 1);
        $this->service->delete($book->id);

        $again = $this->service->create('Libro A', 'Autor', '9780307474728', self::NOVEL, 1);

        $this->assertNotSame($book->id, $again->id);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);
        $this->service->create('Libro', 'Autor', '9780307474728', 'missing-category', 1);
    }

    public function test_update_changes_details_and_total(): void
    {
        $book = $this->service->create('Libro', 'Autor', '9780307474728', self::NOVEL, 2);

        $updated = $this->service->update($book->id, 'Libro nuevo', 'Otro autor', self::NOVEL, 5);

        $this->assertSame('Libro nuevo', $updated->title());
        $this->assertSame(5, $updated->availableCopies());
    }

    public function test_book_with_open_loans_cannot_be_deleted(): void
    {
        $book = $this->service->create('Libro', 'Autor', '9780307474728', self::NOVEL, 1);
        $this->books->booksWithOpenLoans[] = $book->id;

        $this->expectException(BookHasOpenLoans::class);
        $this->service->delete($book->id);
    }

    public function test_deleted_book_is_not_found(): void
    {
        $book = $this->service->create('Libro', 'Autor', '9780307474728', self::NOVEL, 1);
        $this->service->delete($book->id);

        $this->expectException(ResourceNotFound::class);
        $this->service->update($book->id, 'Libro', 'Autor', self::NOVEL, 1);
    }
}
