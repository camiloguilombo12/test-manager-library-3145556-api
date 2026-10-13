<?php

namespace App\Application\Errors;

use DomainException;

/**
 * El socio superaría los 3 libros sin devolver (CA-06.4). Se traduce a 422 MEMBER_LIMIT_EXCEEDED con el conteo actual.
 */
final class MemberLimitExceeded extends DomainException
{
    public function __construct(public readonly int $currentCount)
    {
        parent::__construct("El socio ya tiene {$currentCount} libros sin devolver; el máximo es 3.");
    }
}
