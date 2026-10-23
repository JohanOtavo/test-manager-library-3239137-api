<?php

use App\Adapters\Console\EnsureInitialAdminCommand;
use App\Domain\Exception\BusinessRuleViolation;
use App\Adapters\Http\ErrorResponder;
use App\Adapters\Http\Middleware\AuthenticateToken;
use App\Adapters\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
    )
    ->withCommands([
        EnsureInitialAdminCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'token' => AuthenticateToken::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Una regla de negocio rota es una respuesta prevista, no un fallo del
        // servidor: se contesta con su código y no ensucia el log de errores.
        $exceptions->dontReport([BusinessRuleViolation::class]);

        // Todo error sale con el mismo cuerpo (RNF-4). Nunca trazas ni SQL.
        $exceptions->render(fn (Throwable $e) => ErrorResponder::render($e));
    })
    ->create();
