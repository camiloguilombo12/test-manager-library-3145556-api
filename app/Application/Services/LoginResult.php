<?php

namespace App\Application\Services;

use App\Domain\Entities\UserRole;

final class LoginResult
{
    public function __construct(
        public readonly string $accessToken,
        public readonly int $expiresInSeconds,
        public readonly UserRole $role,
    ) {}
}
