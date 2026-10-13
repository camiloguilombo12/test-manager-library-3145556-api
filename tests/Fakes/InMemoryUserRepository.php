<?php

namespace Tests\Fakes;

use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    public array $users = [];

    public function findByUsername(string $username): ?User
    {
        return $this->users[$username] ?? null;
    }

    public function save(User $user): void
    {
        $this->users[$user->username] = $user;
    }
}
