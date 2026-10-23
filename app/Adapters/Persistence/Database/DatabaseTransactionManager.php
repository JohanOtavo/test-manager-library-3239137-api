<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\TransactionManager;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseTransactionManager implements TransactionManager
{
    public function __construct(private ConnectionInterface $connection) {}

    public function transactional(callable $work): mixed
    {
        return $this->connection->transaction($work);
    }
}
