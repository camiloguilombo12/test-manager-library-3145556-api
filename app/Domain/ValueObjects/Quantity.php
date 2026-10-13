<?php

namespace App\Domain\ValueObjects;

use App\Domain\Errors\InvalidValue;

/**
 * Cantidad de ejemplares: siempre mayor que cero (CA-03.3).
 */
final class Quantity
{
    private function __construct(public readonly int $value) {}

    public static function of(int $value): self
    {
        if ($value < 1) {
            throw new InvalidValue('total_copies', 'El total de ejemplares debe ser al menos 1.');
        }

        return new self($value);
    }
}
