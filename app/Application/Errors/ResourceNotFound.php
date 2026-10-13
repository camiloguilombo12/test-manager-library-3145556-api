<?php

namespace App\Application\Errors;

use DomainException;

/**
 * El recurso no existe o está dado de baja. Se traduce a 404 NOT_FOUND.
 */
final class ResourceNotFound extends DomainException
{
    public function __construct(string $message = 'El recurso no existe.')
    {
        parent::__construct($message);
    }
}
