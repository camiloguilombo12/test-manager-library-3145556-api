<?php

namespace App\Application\Errors;

use DomainException;

/**
 * Usuario o clave incorrectos, sin decir cuál de los dos falló (CA-01.2). Se traduce a 401 INVALID_CREDENTIALS.
 */
final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('Usuario o contraseña incorrectos.');
    }
}
