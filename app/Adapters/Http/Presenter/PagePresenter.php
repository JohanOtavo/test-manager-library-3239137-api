<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use App\Application\Shared\PageResult;

/** El sobre de paginación del contrato (api-contract §1). */
final class PagePresenter
{
    /**
     * @param  PageResult<mixed>  $result
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function of(PageResult $result, array $items): array
    {
        return [
            'items' => $items,
            'page' => $result->page,
            'size' => $result->size,
            'total' => $result->total,
        ];
    }
}
