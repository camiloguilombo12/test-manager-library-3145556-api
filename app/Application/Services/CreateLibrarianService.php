<?php

namespace App\Application\Services;

use App\Application\Errors\UsernameTaken;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;
use App\Domain\Entities\UserRole;
use App\Domain\Errors\InvalidValue;

/**
 * Caso de uso: el admin da de alta un bibliotecario (HU-02).
 */
final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly IdGenerator $ids,
    ) {}

    public function create(string $username, string $password, string $role): User
    {
        $assignedRole = UserRole::assignableByApi($role);

        if (trim($password) === '') {
            throw new InvalidValue('password', 'La contraseña no puede estar vacía.');
        }

        $normalizedUsername = User::normalizeUsername($username);

        if ($this->users->findByUsername($normalizedUsername) !== null) {
            throw new UsernameTaken();
        }

        $user = User::create($this->ids->newId(), $normalizedUsername, $this->hasher->hash($password), $assignedRole);
        $this->users->save($user);

        return $user;
    }
}
