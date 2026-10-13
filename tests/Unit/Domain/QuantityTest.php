<?php

namespace Tests\Unit\Domain;

use App\Domain\Errors\InvalidValue;
use App\Domain\ValueObjects\Quantity;
use PHPUnit\Framework\TestCase;

class QuantityTest extends TestCase
{
    public function test_accepts_positive_quantity(): void
    {
        $this->assertSame(1, Quantity::of(1)->value);
    }

    public function test_rejects_zero(): void
    {
        $this->expectException(InvalidValue::class);
        Quantity::of(0);
    }

    public function test_rejects_negative(): void
    {
        $this->expectException(InvalidValue::class);
        Quantity::of(-3);
    }
}
