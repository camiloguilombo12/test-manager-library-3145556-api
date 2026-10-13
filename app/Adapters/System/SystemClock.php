<?php

namespace App\Adapters\System;

use App\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Reloj real en UTC con desfase opcional en días, que solo se usa para sembrar la demo (spec-review.md E-02).
 */
final class SystemClock implements Clock
{
    public function __construct(private readonly int $offsetDays = 0) {}

    public function now(): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->offsetDays === 0 ? $now : $now->modify("{$this->offsetDays} days");
    }
}
