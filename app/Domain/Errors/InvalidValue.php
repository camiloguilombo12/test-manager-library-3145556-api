<?php

namespace App\Domain\Errors;

use DomainException;

/**
 * Un valor que rompe una regla del dominio. El adaptador HTTP lo traduce a 422 VALIDATION_ERROR.
 */
final class InvalidValue extends DomainException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
