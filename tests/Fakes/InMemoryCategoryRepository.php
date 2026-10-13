<?php

namespace Tests\Fakes;

use App\Application\Ports\CategoryRepository;

final class InMemoryCategoryRepository implements CategoryRepository
{
    /**
     * @param array<string, string> $namesById
     */
    public function __construct(private readonly array $namesById) {}

    public function exists(string $categoryId): bool
    {
        return array_key_exists($categoryId, $this->namesById);
    }

    public function nameOf(string $categoryId): ?string
    {
        return $this->namesById[$categoryId] ?? null;
    }
}
