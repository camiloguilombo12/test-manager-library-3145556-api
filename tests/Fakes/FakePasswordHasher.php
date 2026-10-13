<?php

namespace Tests\Fakes;

use App\Application\Ports\PasswordHasher;

final class FakePasswordHasher implements PasswordHasher
{
    public int $verifyCalls = 0;

    public function hash(string $plainPassword): string
    {
        return 'hashed:'.strrev($plainPassword);
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        $this->verifyCalls++;

        return $this->hash($plainPassword) === $passwordHash;
    }
}
