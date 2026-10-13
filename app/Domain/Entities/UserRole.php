<?php

namespace App\Domain\Entities;

use App\Domain\Errors\InvalidValue;

enum UserRole: string
{
    case Admin = 'admin';
    case Librarian = 'librarian';

    /**
     * Rol pedido por la API al crear un usuario: solo se admite librarian (CA-02.3).
     * El admin lo provisiona el despliegue desde el entorno, nunca la API.
     */
    public static function assignableByApi(string $role): self
    {
        if ($role !== self::Librarian->value) {
            throw new InvalidValue('role', 'Solo se pueden crear usuarios con el rol librarian.');
        }

        return self::Librarian;
    }
}
