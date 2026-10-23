<?php

declare(strict_types=1);

namespace App\Adapters\Http;

use App\Domain\Exception\BusinessRuleViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * El único sitio donde nace un cuerpo de error (RNF-4).
 *
 * Aquí se traduce el código del dominio a un estado HTTP, y aquí se corta todo
 * lo que no debe salir: trazas, SQL, rutas del servidor (api-contract §1).
 */
final class ErrorResponder
{
    /** Estado por código de negocio (api-contract §2). */
    private const STATUS_BY_CODE = [
        'INVALID_CREDENTIALS' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'ISBN_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'BOOK_HAS_OPEN_LOANS' => 409,
        'BOOK_NOT_AVAILABLE' => 409,
        'MEMBER_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'MEMBER_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_BOOK_IN_LOAN' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    public static function render(Throwable $e): JsonResponse
    {
        if ($e instanceof BusinessRuleViolation) {
            return self::body($e->code(), $e->getMessage(), $e->details());
        }

        if ($e instanceof ValidationException) {
            return self::body('VALIDATION_ERROR', 'Hay campos inválidos en la petición.', self::fieldsOf($e));
        }

        if ($e instanceof HttpExceptionInterface) {
            return self::fromStatus($e->getStatusCode());
        }

        // Un fallo no previsto se registra por dentro y por fuera solo dice que
        // algo salió mal: el detalle no viaja al cliente.
        return self::body('INTERNAL_ERROR', 'Ocurrió un error inesperado.', [], 500);
    }

    /** @return list<array{field: string, issue: string}> */
    private static function fieldsOf(ValidationException $e): array
    {
        $details = [];

        foreach ($e->errors() as $field => $issues) {
            foreach ($issues as $issue) {
                $details[] = ['field' => (string) $field, 'issue' => (string) $issue];
            }
        }

        return $details;
    }

    private static function fromStatus(int $status): JsonResponse
    {
        return match ($status) {
            401 => self::body('INVALID_CREDENTIALS', 'Hace falta un token válido.', [], 401),
            403 => self::body('FORBIDDEN_ROLE', 'Su rol no permite esta operación.', [], 403),
            405 => self::body('NOT_FOUND', 'Ese recurso no existe.', [], 404),
            default => self::body('NOT_FOUND', 'Ese recurso no existe.', [], $status === 404 ? 404 : $status),
        };
    }

    /** @param list<array{field: string, issue: string}> $details */
    private static function body(string $code, string $message, array $details = [], ?int $status = null): JsonResponse
    {
        return new JsonResponse(
            [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            $status ?? self::STATUS_BY_CODE[$code] ?? 422,
        );
    }
}
