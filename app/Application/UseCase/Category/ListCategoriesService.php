<?php

declare(strict_types=1);

namespace App\Application\UseCase\Category;

use App\Application\Port\CategoryRepository;

/** Las cinco categorías, orden por nombre (api-contract §3.3). */
final readonly class ListCategoriesService
{
    public function __construct(private CategoryRepository $categories) {}

    /** @return list<\App\Application\Shared\CategoryView> */
    public function handle(): array
    {
        return $this->categories->all();
    }
}
