<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\MultipartBody;
use App\Adapters\Http\Presenter\BookPresenter;
use App\Application\Port\CategoryRepository;
use App\Application\UseCase\Book\GetBookCoverService;
use App\Application\UseCase\Book\RemoveBookCoverService;
use App\Application\UseCase\Book\SetBookCoverService;
use App\Domain\Exception\ValidationFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** HU-03 · Portada del libro (CA-03.4). */
final readonly class BookCoverController
{
    public function __construct(
        private SetBookCoverService $setCover,
        private RemoveBookCoverService $removeCover,
        private GetBookCoverService $getCover,
        private CategoryRepository $categories,
    ) {}

    public function update(Request $request, string $book): JsonResponse
    {
        $updated = $this->setCover->handle($book, $this->sentFile($request));

        return new JsonResponse(BookPresenter::one($updated, $this->categories->find($updated->categoryId)));
    }

    /**
     * El contrato sube la portada con PUT, y PHP solo llena $_FILES en POST.
     * Así que se intenta la vía normal (que sirve si alguien usa POST con
     * _method=PUT) y, si no hay archivo, se lee del cuerpo crudo.
     */
    private function sentFile(Request $request): string
    {
        $file = $request->file('file');

        if ($file !== null && ! is_array($file) && $file->isValid()) {
            return (string) $file->get();
        }

        $contents = MultipartBody::fileContents(
            (string) $request->header('Content-Type', ''),
            $request->getContent(),
        );

        if ($contents === null || $contents === '') {
            throw new ValidationFailed('Hay que enviar un archivo en el campo file.', [
                ['field' => 'file', 'issue' => 'archivo ausente o corrupto'],
            ]);
        }

        return $contents;
    }

    public function destroy(string $book): JsonResponse
    {
        $this->removeCover->handle($book);

        return new JsonResponse(null, 204);
    }

    /** Devuelve el binario; el tipo se lee del contenido, no de la clave. */
    public function show(string $book): Response
    {
        $contents = $this->getCover->handle($book);
        $info = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $info->buffer($contents);

        return new Response($contents, 200, [
            'Content-Type' => $mimeType === false ? 'application/octet-stream' : $mimeType,
        ]);
    }
}
