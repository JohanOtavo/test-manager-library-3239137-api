<?php

declare(strict_types=1);

namespace App\Adapters\Storage;

use App\Application\Port\CoverStorage;
use App\Application\Port\IdGenerator;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Portadas en disco. La clave que devuelve es opaca: no es una ruta que el
 * cliente pueda pedir, solo un nombre que este adaptador sabe resolver (D-08).
 */
final readonly class DiskCoverStorage implements CoverStorage
{
    public function __construct(
        private Filesystem $disk,
        private IdGenerator $ids,
    ) {}

    public function put(string $contents, string $mimeType): string
    {
        $coverKey = $this->ids->next().$this->extensionFor($mimeType);
        $this->disk->put($coverKey, $contents);

        return $coverKey;
    }

    public function read(string $coverKey): ?string
    {
        if (! $this->disk->exists($coverKey)) {
            return null;
        }

        $contents = $this->disk->get($coverKey);

        return $contents === null ? null : (string) $contents;
    }

    public function delete(string $coverKey): void
    {
        if ($this->disk->exists($coverKey)) {
            $this->disk->delete($coverKey);
        }
    }

    private function extensionFor(string $mimeType): string
    {
        return $mimeType === 'image/png' ? '.png' : '.jpg';
    }
}
