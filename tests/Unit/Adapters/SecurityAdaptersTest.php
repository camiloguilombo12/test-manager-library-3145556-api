<?php

namespace Tests\Unit\Adapters;

use App\Adapters\Security\Argon2PasswordHasher;
use App\Adapters\Security\JwtTokens;
use App\Adapters\System\SystemClock;
use App\Adapters\System\UuidGenerator;
use App\Domain\Entities\User;
use App\Domain\Entities\UserRole;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedClock;

class SecurityAdaptersTest extends TestCase
{
    private const SECRET = 'a-very-long-secret-for-tests-only-0123456789';

    public function test_password_hash_is_argon2id_and_verifies(): void
    {
        $hasher = new Argon2PasswordHasher();
        $hash = $hasher->hash('secret-1');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue($hasher->verify('secret-1', $hash));
        $this->assertFalse($hasher->verify('wrong', $hash));
    }

    public function test_token_carries_only_user_id_and_role(): void
    {
        $tokens = new JwtTokens(self::SECRET, 28800, new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z')));
        $issued = $tokens->issue(User::create('user-1', 'ana', 'hash', UserRole::Librarian));

        $payload = json_decode(base64_decode(strtr(explode('.', $issued->accessToken)[1], '-_', '+/')), true);
        $claims = $tokens->verify($issued->accessToken);

        $this->assertSame(28800, $issued->expiresInSeconds);
        $this->assertSame(['sub', 'role', 'iat', 'exp'], array_keys($payload));
        $this->assertSame('user-1', $claims->userId);
        $this->assertSame(UserRole::Librarian, $claims->role);
    }

    public function test_expired_or_tampered_token_is_rejected(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z'));
        $tokens = new JwtTokens(self::SECRET, 28800, $clock);
        $token = $tokens->issue(User::create('user-1', 'ana', 'hash', UserRole::Admin))->accessToken;

        $this->assertNull($tokens->verify($token.'x'));

        $clock->advance('+8 hours +1 second');
        $this->assertNull($tokens->verify($token));
    }

    public function test_short_secret_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JwtTokens('short', 28800, new FixedClock(new DateTimeImmutable()));
    }

    public function test_clock_applies_offset_and_ids_are_uuid_v4(): void
    {
        $difference = (new SystemClock())->now()->getTimestamp() - (new SystemClock(-20))->now()->getTimestamp();

        $this->assertEqualsWithDelta(20 * 86400, $difference, 5);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', (new UuidGenerator())->newId());
    }
}
