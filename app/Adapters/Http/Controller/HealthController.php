<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use Illuminate\Http\JsonResponse;

/** T-01 · Señal de vida, pública y sin token. */
final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
