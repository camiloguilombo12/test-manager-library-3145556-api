<?php

namespace App\Application\Ports;

use App\Domain\Entities\Member;

interface MemberRepository
{
    /**
     * Al prestar, el adaptador bloquea la fila del socio dentro de la transacción (spec-review.md C-03).
     */
    public function findById(string $id): ?Member;
}
