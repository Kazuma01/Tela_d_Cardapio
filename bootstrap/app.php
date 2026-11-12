<?php

use App\Services\AuditLogger;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // O Laravel só confia nos headers X-Forwarded-* vindos do proxy interno
        // do Docker (Caddy/nginx). Sem isto ele acharia que a requisição é HTTP
        // e o cookie Secure deixaria de ser enviado.
        $middleware->trustProxies(
            at: ['172.19.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Derruba as outras sessões do usuário quando a senha muda.
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 403 = tentativa de acessar recurso sem autorização. É evento
        // suspeito e fica registrado na auditoria (sem dados sensíveis).
        // Usamos renderable (e não reportable) porque HttpException não é
        // reportada por padrão; devolver null mantém a resposta 403 normal.
        $exceptions->renderable(function (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 403) {
                AuditLogger::log('security.forbidden', 'Acesso negado pelo backend');
            }

            return null;
        });
    })->create();
