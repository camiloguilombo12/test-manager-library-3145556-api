<?php

namespace App\Adapters\Security;

use App\Application\Ports\Clock;
use App\Application\Ports\IssuedToken;
use App\Application\Ports\TokenClaims;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\TokenVerifier;
use App\Domain\Entities\User;
use App\Domain\Entities\UserRole;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Throwable;

/**
 * JWT HS256 de 8 horas (plan D-11). Solo lleva sub (id del usuario) y role: ningún dato personal (api-contract.md §4).
 */
final class JwtTokens implements TokenIssuer, TokenVerifier
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds,
        private readonly Clock $clock,
    ) {
        // HS256 necesita una clave de al menos 256 bits
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT_SECRET must be at least 32 characters long.');
        }
    }

    public function issue(User $user): IssuedToken
    {
        $issuedAt = $this->clock->now()->getTimestamp();

        $token = JWT::encode([
            'sub' => $user->id,
            'role' => $user->role->value,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttlSeconds,
        ], $this->secret, self::ALGORITHM);

        return new IssuedToken($token, $this->ttlSeconds);
    }

    public function verify(string $token): ?TokenClaims
    {
        // El vencimiento se compara con el mismo reloj que emitió el token
        JWT::$timestamp = $this->clock->now()->getTimestamp();

        try {
            $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
            $role = UserRole::tryFrom((string) ($payload->role ?? ''));

            if (! isset($payload->sub) || $role === null) {
                return null;
            }

            return new TokenClaims((string) $payload->sub, $role);
        } catch (Throwable) {
            return null;
        } finally {
            JWT::$timestamp = null;
        }
    }
}
