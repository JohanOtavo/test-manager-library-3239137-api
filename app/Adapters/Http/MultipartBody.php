<?php

declare(strict_types=1);

namespace App\Adapters\Http;

/**
 * Lee un cuerpo multipart cuando PHP no lo hace por nosotros.
 *
 * PHP solo llena $_FILES en peticiones POST. El contrato pide subir la portada
 * con PUT y multipart/form-data (api-contract §3.4), así que en ese caso el
 * archivo no llega por la vía normal y hay que sacarlo del cuerpo crudo.
 *
 * Es un lector mínimo a propósito: busca la parte que se llama como se le pida
 * y devuelve sus bytes. No interpreta codificaciones ni partes anidadas, porque
 * el contrato solo manda un campo con un archivo.
 */
final class MultipartBody
{
    /** Devuelve los bytes del campo pedido, o null si no está en el cuerpo. */
    public static function fileContents(string $contentType, string $body, string $field = 'file'): ?string
    {
        $boundary = self::boundaryOf($contentType);

        if ($boundary === null || $body === '') {
            return null;
        }

        foreach (explode('--'.$boundary, $body) as $part) {
            if ($part === '' || $part === "--\r\n" || trim($part) === '--') {
                continue;
            }

            $split = strpos($part, "\r\n\r\n");

            if ($split === false) {
                continue;
            }

            $headers = substr($part, 0, $split);

            if (! self::isField($headers, $field)) {
                continue;
            }

            // El cuerpo de la parte termina en el CRLF que precede al separador.
            $contents = substr($part, $split + 4);

            return preg_replace('/\r\n$/', '', $contents) ?? $contents;
        }

        return null;
    }

    private static function boundaryOf(string $contentType): ?string
    {
        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $found) !== 1) {
            return null;
        }

        return $found[1];
    }

    /** La parte sirve si se llama como el campo pedido y trae un nombre de archivo. */
    private static function isField(string $headers, string $field): bool
    {
        if (stripos($headers, 'content-disposition') === false) {
            return false;
        }

        return preg_match('/name="'.preg_quote($field, '/').'"/i', $headers) === 1
            && stripos($headers, 'filename=') !== false;
    }
}
