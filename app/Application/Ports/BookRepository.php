<?php

namespace App\Application\Ports;

use App\Domain\Entities\Book;
use App\Domain\ValueObjects\Isbn;

interface BookRepository
{
    /**
     * Solo libros activos: uno dado de baja se trata como inexistente (CA-04.3).
     */
    public function findActiveById(string $id): ?Book;

    public function activeIsbnExists(Isbn $isbn): bool;

    public function hasOpenLoans(string $bookId): bool;

    /**
     * Inserta o actualiza. Al actualizar compara la versión leída (ADR-002).
     */
    public function save(Book $book): void;
}
