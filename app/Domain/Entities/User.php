<?php

namespace App\Domain\Entities;

use App\Domain\Errors\InvalidValue;

/**
 * Operador interno. El dominio nunca ve la clave en claro: solo recibe el hash del puerto PasswordHasher (artículo IX).
 */
final class User
{
    private function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly UserRole $role,
    ) {}

    public static function create(string $id, string $username, string $passwordHash, UserRole $role): self
    {
        return new self($id, self::normalizeUsername($username), $passwordHash, $role);
    }

    /**
     * Minúsculas y sin espacios en los extremos (CA-02.2): "Ana " queda "ana".
     */
    public static function normalizeUsername(string $username): string
    {
        $normalized = mb_strtolower(trim($username));

        if ($normalized === '') {
            throw new InvalidValue('username', 'El nombre de usuario no puede estar vacío.');
        }

        return $normalized;
    }
}
