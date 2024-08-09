<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain;

final class UserRepository
{
    /**
     * @var array<string, User>
     */
    private array $users = [];

    public function add(User $user): void
    {
        $this->users[strtolower($user->name)] = $user;
    }

    public function findByName(string $name): ?User
    {
        return $this->users[strtolower($name)] ?? null;
    }

    /**
     * @return list<User>
     */
    public function all(): array
    {
        return array_values($this->users);
    }
}
