<?php

namespace App\Domain\Entities;

/**
 * Estado del préstamo. Se calcula, no se almacena (artículo VII).
 */
enum LoanStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
