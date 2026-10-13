<?php

namespace App\Domain\ValueObjects;

use App\Domain\Errors\InvalidValue;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Rango de fechas inclusivo en UTC (CA-10.2, spec-review.md A-02).
 */
final class DateRange
{
    private function __construct(
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {}

    public static function fromDates(string $from, string $to): self
    {
        $utc = new DateTimeZone('UTC');
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $from, $utc);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $to, $utc);

        if ($start === false || $end === false) {
            throw new InvalidValue('from', 'Las fechas deben tener el formato AAAA-MM-DD.');
        }

        if ($end < $start) {
            throw new InvalidValue('to', 'La fecha final no puede ser anterior a la inicial.');
        }

        return new self($start, $end);
    }

    /**
     * Límite superior exclusivo: el día siguiente a la fecha final a las 00:00 UTC,
     * así el último día cuenta completo.
     */
    public function exclusiveEnd(): DateTimeImmutable
    {
        return $this->to->modify('+1 day');
    }

    public function contains(DateTimeImmutable $instant): bool
    {
        return $instant >= $this->from && $instant < $this->exclusiveEnd();
    }
}
