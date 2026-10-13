<?php

namespace App\Application\Ports;

use App\Domain\Entities\User;

/**
 * Emisión del token de acceso (plan D-11): JWT de 8 horas con sub y role, sin datos personales.
 */
interface TokenIssuer
{
    public function issue(User $user): IssuedToken;
}
