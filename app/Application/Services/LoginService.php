<?php

namespace App\Application\Services;

use App\Application\Errors\InvalidCredentials;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;
use App\Domain\Errors\InvalidValue;

/**
 * Caso de uso: iniciar sesión (HU-01).
 */
final class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
    ) {}

    public function login(string $username, string $password): LoginResult
    {
        try {
            $user = $this->users->findByUsername(User::normalizeUsername($username));
        } catch (InvalidValue) {
            $user = null;
        }

        if ($user === null) {
            // Se verifica igual contra un hash ficticio para que el tiempo no delate si el usuario existe (spec-review.md A-12)
            $this->hasher->verify($password, $this->hasher->hash('not-a-real-password'));

            throw new InvalidCredentials();
        }

        if (! $this->hasher->verify($password, $user->passwordHash)) {
            throw new InvalidCredentials();
        }

        $token = $this->tokens->issue($user);

        return new LoginResult($token->accessToken, $token->expiresInSeconds, $user->role);
    }
}
