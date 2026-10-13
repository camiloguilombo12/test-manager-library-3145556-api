<?php

namespace App\Domain\Errors;

use DomainException;

/**
 * La línea ya fue devuelta (CA-07.2). Se traduce a 409 ITEM_ALREADY_RETURNED.
 */
final class ItemAlreadyReturned extends DomainException
{
    public function __construct(public readonly string $itemId)
    {
        parent::__construct('Esta línea del préstamo ya fue devuelta.');
    }
}
