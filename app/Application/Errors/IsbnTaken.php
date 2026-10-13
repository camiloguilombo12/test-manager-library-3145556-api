<?php

namespace App\Application\Errors;

use DomainException;

/**
 * Ya existe un libro activo con ese ISBN (CA-03.2). Se traduce a 409 ISBN_TAKEN.
 */
final class IsbnTaken extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe un libro activo con ese ISBN.');
    }
}
