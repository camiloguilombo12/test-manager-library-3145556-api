<?php

namespace App\Application\Errors;

use DomainException;

/**
 * El nombre de usuario ya existe (CA-02.2). Se traduce a 409 USERNAME_TAKEN.
 */
final class UsernameTaken extends DomainException
{
    public function __construct()
    {
        parent::__construct('El nombre de usuario ya está en uso.');
    }
}
