<?php

namespace App\Domain\Errors;

use DomainException;

/**
 * La línea no pertenece al préstamo (spec-review.md A-08). Se traduce a 404 NOT_FOUND.
 */
final class LoanItemNotFound extends DomainException
{
    public function __construct(public readonly string $itemId)
    {
        parent::__construct('La línea no existe en este préstamo.');
    }
}
