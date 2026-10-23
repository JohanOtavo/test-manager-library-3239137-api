<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\CategoryRepository;
use App\Application\Shared\CategoryView;

final class InMemoryCategories implements CategoryRepository
{
    /** @var array<string, CategoryView> */
    private array $categories = [];

    public function add(string $id, string $name): void
    {
        $this->categories[$id] = new CategoryView($id, $name);
    }

    public function all(): array
    {
        return array_values($this->categories);
    }

    public function find(string $id): ?CategoryView
    {
        return $this->categories[$id] ?? null;
    }
}
