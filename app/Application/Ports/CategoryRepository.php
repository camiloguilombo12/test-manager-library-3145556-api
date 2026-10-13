<?php

namespace App\Application\Ports;

interface CategoryRepository
{
    public function exists(string $categoryId): bool;
}
