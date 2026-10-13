<?php

namespace App\Domain\Errors;

use DomainException;

/**
 * No quedan ejemplares disponibles del libro (CA-06.3). El adaptador HTTP lo traduce a 409 BOOK_NOT_AVAILABLE.
 */
final class BookNotAvailable extends DomainException
{
    public function __construct(public readonly string $bookId, string $title)
    {
        parent::__construct("No quedan ejemplares disponibles de «{$title}».");
    }
}
