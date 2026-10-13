<?php

namespace App\Application\Ports;

interface CategoryRepository
{
    public function exists(string $categoryId): bool;

    /**
     * Nombre actual de la categoría, que la línea de préstamo congela (CA-06.6).
     */
    public function nameOf(string $categoryId): ?string;
}
