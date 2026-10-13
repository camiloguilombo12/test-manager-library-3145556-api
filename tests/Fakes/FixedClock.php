<?php

namespace Tests\Fakes;

use App\Application\Ports\Clock;
use DateTimeImmutable;

/**
 * Reloj falso para pruebas: devuelve siempre el instante indicado y se puede adelantar.
 */
final class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
