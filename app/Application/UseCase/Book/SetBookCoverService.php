<?php

declare(strict_types=1);

namespace App\Application\UseCase\Book;

use App\Application\Port\BookRepository;
use App\Application\Port\CoverStorage;
use App\Domain\Book\Book;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;

/**
 * HU-03 · Sube la portada (CA-03.4). El tipo se mira por contenido y no por
 * extensión (api-contract §4): quien renombra un .exe a .png no pasa.
 */
final readonly class SetBookCoverService
{
    /** @param list<string> $allowedMimeTypes */
    public function __construct(
        private BookRepository $books,
        private CoverStorage $covers,
        private int $maxBytes,
        private array $allowedMimeTypes,
    ) {}

    public function handle(string $bookId, string $contents): Book
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFound('Ese libro no existe o está dado de baja.');
        }

        if ($contents === '' || strlen($contents) > $this->maxBytes) {
            throw new ValidationFailed(
                sprintf('La portada no puede pasar de %d KB.', intdiv($this->maxBytes, 1024)),
                [['field' => 'file', 'issue' => 'tamaño fuera de rango']],
            );
        }

        $mimeType = $this->detectMimeType($contents);

        if (! in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new ValidationFailed('La portada debe ser JPG o PNG.', [
                ['field' => 'file', 'issue' => 'tipo no admitido'],
            ]);
        }

        // Si ya tenía portada, el binario viejo se va cuando el nuevo está puesto.
        $previousKey = $book->coverKey;
        $updated = $book->attachCover($this->covers->put($contents, $mimeType));
        $this->books->update($updated);

        if ($previousKey !== null) {
            $this->covers->delete($previousKey);
        }

        return $updated;
    }

    private function detectMimeType(string $contents): string
    {
        $info = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $info->buffer($contents);

        return $mimeType === false ? 'application/octet-stream' : $mimeType;
    }
}
