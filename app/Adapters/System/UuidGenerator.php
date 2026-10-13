<?php

namespace App\Adapters\System;

use App\Application\Ports\IdGenerator;
use Illuminate\Support\Str;

/**
 * UUID v4 (api-contract.md §1). No se usa HasUuids porque en Laravel 12 genera v7.
 */
final class UuidGenerator implements IdGenerator
{
    public function newId(): string
    {
        return Str::uuid()->toString();
    }
}
