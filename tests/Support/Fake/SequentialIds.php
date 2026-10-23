<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\IdGenerator;

/** Identificadores predecibles: id-1, id-2... para poder afirmar sobre ellos. */
final class SequentialIds implements IdGenerator
{
    private int $issued = 0;

    public function next(): string
    {
        $this->issued++;

        return 'id-'.$this->issued;
    }
}
