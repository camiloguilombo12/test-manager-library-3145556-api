<?php

namespace Tests\Unit\Domain;

use App\Domain\Errors\InvalidValue;
use App\Domain\ValueObjects\DateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function test_same_day_is_valid(): void
    {
        $range = DateRange::fromDates('2026-10-01', '2026-10-01');

        $this->assertTrue($range->contains(new DateTimeImmutable('2026-10-01T23:59:59Z')));
    }

    public function test_last_day_counts_until_midnight(): void
    {
        $range = DateRange::fromDates('2026-10-01', '2026-10-31');

        $this->assertTrue($range->contains(new DateTimeImmutable('2026-10-31T22:00:00Z')));
        $this->assertFalse($range->contains(new DateTimeImmutable('2026-11-01T00:00:00Z')));
    }

    public function test_rejects_end_before_start(): void
    {
        $this->expectException(InvalidValue::class);
        DateRange::fromDates('2026-10-31', '2026-10-01');
    }

    public function test_rejects_invalid_format(): void
    {
        $this->expectException(InvalidValue::class);
        DateRange::fromDates('31/10/2026', '2026-11-01');
    }
}
