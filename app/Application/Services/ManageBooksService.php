<?php

namespace App\Application\Services;

use App\Application\Errors\BookHasOpenLoans;
use App\Application\Errors\IsbnTaken;
use App\Application\Errors\ResourceNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Domain\Entities\Book;
use App\Domain\Errors\InvalidValue;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;

/**
 * Casos de uso del admin sobre el catálogo: crear, editar y dar de baja libros (HU-03).
 */
final class ManageBooksService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CategoryRepository $categories,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {}

    public function create(string $title, string $author, string $isbn, string $categoryId, int $totalCopies): Book
    {
        $validIsbn = Isbn::fromString($isbn);
        $this->ensureCategoryExists($categoryId);

        if ($this->books->activeIsbnExists($validIsbn)) {
            throw new IsbnTaken();
        }

        $book = Book::create($this->ids->newId(), $title, $author, $validIsbn, $categoryId, Quantity::of($totalCopies));
        $this->books->save($book);

        return $book;
    }

    public function update(string $bookId, string $title, string $author, string $categoryId, int $totalCopies): Book
    {
        $book = $this->findActive($bookId);
        $this->ensureCategoryExists($categoryId);

        $book->updateDetails($title, $author, $categoryId);
        $book->changeTotalCopies(Quantity::of($totalCopies));
        $this->books->save($book);

        return $book;
    }

    /**
     * Baja lógica: un libro con préstamos abiertos no se da de baja (CA-03.5, ADR-003).
     */
    public function delete(string $bookId): void
    {
        $book = $this->findActive($bookId);

        if ($this->books->hasOpenLoans($bookId)) {
            throw new BookHasOpenLoans();
        }

        $book->markAsDeleted($this->clock->now());
        $this->books->save($book);
    }

    private function findActive(string $bookId): Book
    {
        return $this->books->findActiveById($bookId) ?? throw new ResourceNotFound('El libro no existe.');
    }

    private function ensureCategoryExists(string $categoryId): void
    {
        if (! $this->categories->exists($categoryId)) {
            throw new InvalidValue('category_id', 'La categoría no existe.');
        }
    }
}
