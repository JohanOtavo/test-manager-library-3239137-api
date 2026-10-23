<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Application\Shared\CategoryView;
use App\Application\UseCase\Category\ListCategoriesService;
use Illuminate\Http\JsonResponse;

/** GET /categories. Cualquier usuario autenticado. */
final readonly class CategoryController
{
    public function __construct(private ListCategoriesService $listCategories) {}

    public function index(): JsonResponse
    {
        $items = array_map(
            fn (CategoryView $category): array => ['id' => $category->id, 'name' => $category->name],
            $this->listCategories->handle(),
        );

        return new JsonResponse(['items' => $items]);
    }
}
