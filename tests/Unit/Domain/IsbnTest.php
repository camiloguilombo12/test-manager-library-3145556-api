<?php

namespace Tests\Unit\Domain;

use App\Domain\Errors\InvalidValue;
use App\Domain\ValueObjects\Isbn;
use PHPUnit\Framework\TestCase;

class IsbnTest extends TestCase
{
    public function test_accepts_thirteen_digits(): void
    {
        $this->assertSame('9780307474728', Isbn::fromString(' 9780307474728 ')->value);
    }

    public function test_rejects_less_than_thirteen_digits(): void
    {
        $this->expectException(InvalidValue::class);
        Isbn::fromString('978030747472');
    }

    public function test_rejects_non_digits(): void
    {
        $this->expectException(InvalidValue::class);
        Isbn::fromString('978-030747472');
    }
}
