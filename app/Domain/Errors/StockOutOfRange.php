<?php

namespace App\Domain\Errors;

use DomainException;

/**
 * Reponer un ejemplar dejaría los disponibles por encima del total (RN-1). Es un defecto, no un caso de negocio.
 */
final class StockOutOfRange extends DomainException
{
}
