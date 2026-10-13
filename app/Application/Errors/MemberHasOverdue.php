<?php

namespace App\Application\Errors;

use DomainException;

/**
 * El socio tiene libros vencidos y no puede pedir otro (CA-06.5). Se traduce a 409 MEMBER_HAS_OVERDUE.
 */
final class MemberHasOverdue extends DomainException
{
    public function __construct()
    {
        parent::__construct('El socio tiene libros vencidos y no puede pedir otro hasta devolverlos.');
    }
}
