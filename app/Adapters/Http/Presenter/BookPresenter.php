<?php

declare(strict_types=1);

namespace App\Adapters\Http\Presenter;

use App\Application\Shared\CategoryView;
use App\Domain\Book\Book;

/** Forma del libro en la respuesta (api-contract §3.4). */
final class BookPresenter
{
    /** @return array<string, mixed> */
    public static function one(Book $book, ?CategoryView $category): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn->value,
            'category' => [
                'id' => $book->categoryId,
                'name' => $category?->name,
            ],
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            // Sin portada es null, nunca cadena vacía (CA-03.4).
            'cover_key' => $book->coverKey,
        ];
    }

    /**
     * @param  list<Book>  $books
     * @param  array<string, CategoryView>  $categoriesById
     * @return list<array<string, mixed>>
     */
    public static function many(array $books, array $categoriesById): array
    {
        return array_map(
            fn (Book $book): array => self::one($book, $categoriesById[$book->categoryId] ?? null),
            $books,
        );
    }
}
