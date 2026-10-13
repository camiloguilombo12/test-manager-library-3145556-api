<?php

namespace App\Application\Errors;

use DomainException;

/**
 * El libro tiene préstamos abiertos y no se puede dar de baja (CA-03.5). Se traduce a 409 BOOK_HAS_OPEN_LOANS.
 */
final class BookHasOpenLoans extends DomainException
{
    public function __construct()
    {
        parent::__construct('El libro tiene préstamos abiertos y no se puede dar de baja.');
    }
}
