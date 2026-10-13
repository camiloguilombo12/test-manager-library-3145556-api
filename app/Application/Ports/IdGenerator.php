<?php

namespace App\Application\Ports;

/**
 * Genera identificadores UUID v4 (api-contract.md §1).
 */
interface IdGenerator
{
    public function newId(): string;
}
