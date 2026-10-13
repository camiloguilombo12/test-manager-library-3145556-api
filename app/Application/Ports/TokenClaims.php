<?php

namespace App\Application\Ports;

use App\Domain\Entities\UserRole;

final class TokenClaims
{
    public function __construct(
        public readonly string $userId,
        public readonly UserRole $role,
    ) {}
}
