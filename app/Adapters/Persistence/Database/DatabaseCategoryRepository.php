<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\CategoryRepository;
use App\Application\Shared\CategoryView;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseCategoryRepository implements CategoryRepository
{
    public function __construct(private ConnectionInterface $connection) {}

    public function all(): array
    {
        $rows = $this->connection->table(Tables::CATEGORY)->orderBy('name')->get();

        return array_map(
            fn (object $row): CategoryView => new CategoryView((string) $row->id, (string) $row->name),
            $rows->all(),
        );
    }

    public function find(string $id): ?CategoryView
    {
        $row = $this->connection->table(Tables::CATEGORY)->where('id', $id)->first();

        return $row === null ? null : new CategoryView((string) $row->id, (string) $row->name);
    }
}
