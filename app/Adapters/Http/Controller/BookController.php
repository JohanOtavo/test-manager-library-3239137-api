<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Presenter\BookPresenter;
use App\Adapters\Http\Presenter\PagePresenter;
use App\Application\Port\CategoryRepository;
use App\Application\Shared\CategoryView;
use App\Application\Shared\Pagination;
use App\Application\UseCase\Book\CreateBookService;
use App\Application\UseCase\Book\GetBookService;
use App\Application\UseCase\Book\SearchBooksService;
use App\Application\UseCase\Book\SoftDeleteBookService;
use App\Application\UseCase\Book\UpdateBookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-03 y HU-04 · /books. */
final readonly class BookController
{
    public function __construct(
        private SearchBooksService $searchBooks,
        private GetBookService $getBook,
        private CreateBookService $createBook,
        private UpdateBookService $updateBook,
        private SoftDeleteBookService $softDeleteBook,
        private CategoryRepository $categories,
        private int $pageSizeMax,
        private int $pageSizeDefault,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = Pagination::of(
            $request->integer('page', 1),
            $request->integer('size', $this->pageSizeDefault),
            $this->pageSizeMax,
            $this->pageSizeDefault,
        );

        $result = $this->searchBooks->handle(
            $request->query('q'),
            $request->query('category_id'),
            $page,
        );

        return new JsonResponse(PagePresenter::of(
            $result,
            BookPresenter::many($result->items, $this->categoriesById()),
        ));
    }

    public function show(string $book): JsonResponse
    {
        $found = $this->getBook->handle($book);

        return new JsonResponse(BookPresenter::one($found, $this->categories->find($found->categoryId)));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'isbn' => ['required', 'string'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer'],
        ]);

        $created = $this->createBook->handle(
            $input['title'],
            $input['author'],
            $input['isbn'],
            $input['category_id'],
            (int) $input['total_copies'],
        );

        return new JsonResponse(
            BookPresenter::one($created, $this->categories->find($created->categoryId)),
            201,
        );
    }

    public function update(Request $request, string $book): JsonResponse
    {
        $input = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer'],
        ]);

        $updated = $this->updateBook->handle(
            $book,
            $input['title'],
            $input['author'],
            $input['category_id'],
            (int) $input['total_copies'],
        );

        return new JsonResponse(BookPresenter::one($updated, $this->categories->find($updated->categoryId)));
    }

    public function destroy(string $book): JsonResponse
    {
        $this->softDeleteBook->handle($book);

        return new JsonResponse(null, 204);
    }

    /** Son cinco: se traen una vez y se reparten entre las filas de la página. */
    private function categoriesById(): array
    {
        $byId = [];

        foreach ($this->categories->all() as $category) {
            $byId[$category->id] = $category;
        }

        return $byId;
    }
}
