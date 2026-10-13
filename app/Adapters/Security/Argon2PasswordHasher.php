<?php

namespace App\Adapters\Security;

use App\Application\Ports\PasswordHasher;

/**
 * Hash Argon2id (plan D-09). La clave en claro no se guarda ni se registra en ningún log.
 */
final class Argon2PasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_ARGON2ID);
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return password_verify($plainPassword, $passwordHash);
    }
}
