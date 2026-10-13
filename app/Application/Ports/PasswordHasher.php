<?php

namespace App\Application\Ports;

/**
 * Hash de contraseñas (plan D-09). La clave en claro nunca sale de este puerto ni llega a un log.
 */
interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function verify(string $plainPassword, string $passwordHash): bool;
}
