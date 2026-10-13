<?php

namespace Tests\Unit\Application;

use App\Application\Errors\InvalidCredentials;
use App\Application\Errors\UsernameTaken;
use App\Application\Services\CreateLibrarianService;
use App\Application\Services\LoginService;
use App\Domain\Entities\UserRole;
use App\Domain\Errors\InvalidValue;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakePasswordHasher;
use Tests\Fakes\FakeTokenIssuer;
use Tests\Fakes\InMemoryUserRepository;
use Tests\Fakes\SequentialIdGenerator;

class AuthServicesTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakePasswordHasher $hasher;
    private CreateLibrarianService $createLibrarian;
    private LoginService $login;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->hasher = new FakePasswordHasher();
        $this->createLibrarian = new CreateLibrarianService($this->users, $this->hasher, new SequentialIdGenerator());
        $this->login = new LoginService($this->users, $this->hasher, new FakeTokenIssuer());
    }

    public function test_creates_librarian_with_normalized_name_and_hashed_password(): void
    {
        $user = $this->createLibrarian->create('Ana ', 'secret-1', 'librarian');

        $this->assertSame('ana', $user->username);
        $this->assertSame(UserRole::Librarian, $user->role);
        $this->assertNotSame('secret-1', $user->passwordHash);
    }

    public function test_repeated_username_is_rejected(): void
    {
        $this->createLibrarian->create('ana', 'secret-1', 'librarian');

        $this->expectException(UsernameTaken::class);
        $this->createLibrarian->create(' ANA', 'secret-2', 'librarian');
    }

    public function test_admin_role_cannot_be_requested(): void
    {
        $this->expectException(InvalidValue::class);
        $this->createLibrarian->create('boss', 'secret-1', 'admin');
    }

    public function test_login_with_valid_credentials_returns_token_and_role(): void
    {
        $this->createLibrarian->create('ana', 'secret-1', 'librarian');

        $result = $this->login->login('Ana', 'secret-1');

        $this->assertSame('token-for-id-1', $result->accessToken);
        $this->assertSame(28800, $result->expiresInSeconds);
        $this->assertSame(UserRole::Librarian, $result->role);
    }

    public function test_wrong_password_and_unknown_user_fail_the_same_way(): void
    {
        $this->createLibrarian->create('ana', 'secret-1', 'librarian');
        $messages = [];

        foreach ([['ana', 'wrong'], ['nobody', 'secret-1']] as [$username, $password]) {
            try {
                $this->login->login($username, $password);
            } catch (InvalidCredentials $error) {
                $messages[] = $error->getMessage();
            }
        }

        $this->assertSame(['Usuario o contraseña incorrectos.', 'Usuario o contraseña incorrectos.'], $messages);
        // También se verifica un hash cuando el usuario no existe
        $this->assertSame(2, $this->hasher->verifyCalls);
    }
}
