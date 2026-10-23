<?php

declare(strict_types=1);

namespace App\Domain\Book;

use App\Domain\Exception\BookNotAvailable;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\Quantity;
use DateTimeImmutable;

/**
 * Un título del catálogo. Los ejemplares se cuentan, no se identifican.
 * Inmutable: cada operación devuelve un libro nuevo, así ninguna regla queda a medias.
 */
final readonly class Book
{
    private function __construct(
        public string $id,
        public string $title,
        public string $author,
        public Isbn $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    /** Al nacer, los disponibles arrancan iguales al total (CA-03.1). */
    public static function register(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        Quantity $totalCopies,
    ): self {
        return new self(
            $id,
            self::cleanTitle($title),
            self::cleanAuthor($author),
            $isbn,
            $categoryId,
            $totalCopies->value,
            $totalCopies->value,
            null,
            null,
        );
    }

    /** Reconstruye un libro guardado. Solo lo usa la persistencia. */
    public static function restore(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        int $totalCopies,
        int $availableCopies,
        ?string $coverKey,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self($id, $title, $author, $isbn, $categoryId, $totalCopies, $availableCopies, $coverKey, $deletedAt);
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /** Saca un ejemplar al prestar. Sin ejemplares no hay préstamo (CA-06.3). */
    public function withdraw(): self
    {
        if ($this->availableCopies < 1) {
            throw new BookNotAvailable(sprintf('No quedan ejemplares disponibles de "%s".', $this->title));
        }

        return $this->withCopies($this->totalCopies, $this->availableCopies - 1);
    }

    /** Devuelve un ejemplar. Los disponibles no superan el total (CA-07.3). */
    public function restock(): self
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new ValidationFailed('Los ejemplares disponibles no pueden superar el total.', [
                ['field' => 'available_copies', 'issue' => 'superaría el total'],
            ]);
        }

        return $this->withCopies($this->totalCopies, $this->availableCopies + 1);
    }

    /** Cambiar el total mueve los disponibles igual y nunca los deja negativos (CA-03.3). */
    public function replaceDetails(string $title, string $author, string $categoryId, Quantity $totalCopies): self
    {
        $available = $this->availableCopies + ($totalCopies->value - $this->totalCopies);

        if ($available < 0) {
            throw new ValidationFailed('Bajar el total dejaría disponibles en negativo: hay copias prestadas.', [
                ['field' => 'total_copies', 'issue' => 'dejaría disponibles en negativo'],
            ]);
        }

        return new self(
            $this->id,
            self::cleanTitle($title),
            self::cleanAuthor($author),
            $this->isbn,
            $categoryId,
            $totalCopies->value,
            $available,
            $this->coverKey,
            $this->deletedAt,
        );
    }

    /** Baja lógica: sale del catálogo y sigue en los préstamos pasados (CA-03.5). */
    public function softDelete(DateTimeImmutable $now): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->author,
            $this->isbn,
            $this->categoryId,
            $this->totalCopies,
            $this->availableCopies,
            $this->coverKey,
            $now,
        );
    }

    /** Clave opaca, nunca una ruta ni una cadena vacía (CA-03.4). */
    public function attachCover(string $coverKey): self
    {
        $key = trim($coverKey);

        if ($key === '') {
            throw new ValidationFailed('La clave de la portada no puede estar vacía.', [
                ['field' => 'cover_key', 'issue' => 'vacía'],
            ]);
        }

        return $this->withCover($key);
    }

    public function removeCover(): self
    {
        return $this->withCover(null);
    }

    private function withCopies(int $total, int $available): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->author,
            $this->isbn,
            $this->categoryId,
            $total,
            $available,
            $this->coverKey,
            $this->deletedAt,
        );
    }

    private function withCover(?string $coverKey): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->author,
            $this->isbn,
            $this->categoryId,
            $this->totalCopies,
            $this->availableCopies,
            $coverKey,
            $this->deletedAt,
        );
    }

    private static function cleanTitle(string $raw): string
    {
        $title = trim($raw);

        if ($title === '' || mb_strlen($title) > 250) {
            throw new ValidationFailed('El título admite entre 1 y 250 caracteres.', [
                ['field' => 'title', 'issue' => 'entre 1 y 250 caracteres'],
            ]);
        }

        return $title;
    }

    private static function cleanAuthor(string $raw): string
    {
        $author = trim($raw);

        if ($author === '' || mb_strlen($author) > 200) {
            throw new ValidationFailed('El autor admite entre 1 y 200 caracteres.', [
                ['field' => 'author', 'issue' => 'entre 1 y 200 caracteres'],
            ]);
        }

        return $author;
    }
}
