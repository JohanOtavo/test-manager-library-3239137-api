<?php

declare(strict_types=1);

namespace App\Application\Port;

/** Los UUID v4 los pone la aplicación, no la base. */
interface IdGenerator
{
    public function next(): string;
}
