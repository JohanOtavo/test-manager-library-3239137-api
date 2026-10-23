<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Raíz de todas las reglas de negocio rotas.
 *
 * El dominio no sabe nada de HTTP: solo nombra el código del contrato y el
 * mensaje en español. Traducir ese código a un estado es trabajo del adaptador.
 */
abstract class BusinessRuleViolation extends RuntimeException
{
    /** @param list<array{field: string, issue: string}> $details */
    public function __construct(string $message, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    abstract public function code(): string;

    /** @return list<array{field: string, issue: string}> */
    public function details(): array
    {
        return $this->details;
    }
}
