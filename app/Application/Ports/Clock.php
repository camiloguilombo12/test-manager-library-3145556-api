<?php

namespace App\Application\Ports;

use DateTimeImmutable;

/**
 * Reloj del sistema como puerto, para probar vencimientos sin esperar 14 días (plan D-05).
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
