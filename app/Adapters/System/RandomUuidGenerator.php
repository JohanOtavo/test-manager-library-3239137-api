<?php

declare(strict_types=1);

namespace App\Adapters\System;

use App\Application\Port\IdGenerator;
use Random\RandomException;
use RuntimeException;

/**
 * UUID v4 con bytes aleatorios del sistema. Sin librería externa: son dieciséis
 * bytes y dos que se fijan para marcar versión y variante.
 */
final class RandomUuidGenerator implements IdGenerator
{
    public function next(): string
    {
        try {
            $bytes = random_bytes(16);
        } catch (RandomException $e) {
            throw new RuntimeException('No hay fuente de aleatoriedad disponible.', 0, $e);
        }

        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
