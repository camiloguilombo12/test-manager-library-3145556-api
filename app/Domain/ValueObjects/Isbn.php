<?php

namespace App\Domain\ValueObjects;

use App\Domain\Errors\InvalidValue;

/**
 * ISBN de 13 dígitos (CA-03.2). Se guarda solo con dígitos.
 */
final class Isbn
{
    private function __construct(public readonly string $value) {}

    public static function fromString(string $value): self
    {
        $digits = trim($value);

        if (preg_match('/^[0-9]{13}$/', $digits) !== 1) {
            throw new InvalidValue('isbn', 'El ISBN debe tener 13 dígitos.');
        }

        return new self($digits);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
