<?php

namespace Tests\Unit\Application;

use App\Application\Ports\OverdueLine;
use App\Application\Ports\OverdueReader;
use App\Application\Services\OverdueService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FixedClock;

class OverdueServiceTest extends TestCase
{
    public function test_lists_overdue_lines_with_most_days_late_first(): void
    {
        $reader = new class implements OverdueReader
        {
            public function findOverdueLines(DateTimeImmutable $now): array
            {
                return [
                    new OverdueLine('loan-1', 'item-1', 'Libro reciente', 'Ana', new DateTimeImmutable('2026-10-14T12:00:00Z')),
                    new OverdueLine('loan-2', 'item-2', 'Libro antiguo', 'Luis', new DateTimeImmutable('2026-10-04T14:00:00Z')),
                ];
            }
        };
        $service = new OverdueService($reader, new FixedClock(new DateTimeImmutable('2026-10-14T14:00:00Z')));

        $rows = $service->list();

        $this->assertSame(['Libro antiguo', 'Libro reciente'], array_column($rows, 'book_title'));
        $this->assertSame([10, 1], array_column($rows, 'days_late'));
        $this->assertSame('2026-10-04T14:00:00Z', $rows[0]['due_at']);
        $this->assertSame('Luis', $rows[0]['member_name']);
    }
}
