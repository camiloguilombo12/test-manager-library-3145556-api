<?php

namespace Tests\Fakes;

use App\Application\Ports\CategoryRepository;

final class InMemoryCategoryRepository implements CategoryRepository
{
    /**
     * @param string[] $categoryIds
     */
    public function __construct(private readonly array $categoryIds) {}

    public function exists(string $categoryId): bool
    {
        return in_array($categoryId, $this->categoryIds, true);
    }
}
