<?php

namespace App\Application\Errors;

use DomainException;

/**
 * Rango de fechas inválido en el reporte (CA-10.2). Se traduce a 422 INVALID_DATE_RANGE.
 */
final class InvalidDateRange extends DomainException
{
}
