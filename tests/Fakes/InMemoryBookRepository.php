<?php

namespace Tests\Fakes;

use App\Application\Ports\BookRepository;
use App\Domain\Entities\Book;
use App\Domain\ValueObjects\Isbn;

final class InMemoryBookRepository implements BookRepository
{
    /** @var array<string, Book> */
    public array $books = [];

    /** @var string[] */
    public array $booksWithOpenLoans = [];

    public function findActiveById(string $id): ?Book
    {
        $book = $this->books[$id] ?? null;

        return $book !== null && $book->isActive() ? $book : null;
    }

    public function activeIsbnExists(Isbn $isbn): bool
    {
        foreach ($this->books as $book) {
            if ($book->isActive() && $book->isbn->equals($isbn)) {
                return true;
            }
        }

        return false;
    }

    public function hasOpenLoans(string $bookId): bool
    {
        return in_array($bookId, $this->booksWithOpenLoans, true);
    }

    public function save(Book $book): void
    {
        $this->books[$book->id] = $book;
    }
}
