<?php

namespace Tests\Fakes;

use App\Application\Ports\IdGenerator;

final class SequentialIdGenerator implements IdGenerator
{
    private int $next = 1;

    public function newId(): string
    {
        return 'id-'.$this->next++;
    }
}
