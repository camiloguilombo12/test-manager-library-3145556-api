<?php

namespace App\Domain\Errors;

use DomainException;

/**
 * El mismo libro aparece dos veces en un préstamo (CA-06.2). Se traduce a 422 DUPLICATE_BOOK_IN_LOAN.
 */
final class DuplicateBookInLoan extends DomainException
{
    public function __construct(public readonly string $bookId)
    {
        parent::__construct('Un préstamo no puede repetir el mismo libro.');
    }
}
