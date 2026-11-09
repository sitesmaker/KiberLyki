<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, \App\Http\Middleware\TrustedProxies::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (Throwable $e, Request $r) {
            if ($r->is('admin/*') && $r->isMethod('POST') && $e instanceof HttpExceptionInterface && $e->getStatusCode() === 422) {
                return back()->withErrors(['operation' => $e->getMessage()])->withInput($r->except(['password', 'password_confirmation', 'current_password', 'data.password', '_token']));
            }
            if (! $r->is('api/*')) {
                return null;
            }
            $status = $e instanceof ValidationException ? 422
                : ($e instanceof AuthenticationException ? 401
                : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500));
            $message = $e instanceof ValidationException ? collect($e->errors())->flatten()->first() : ($status === 500 ? 'Ошибка сервера' : $e->getMessage());

            return response()->json(['error' => ['message' => $message ?: 'Ошибка запроса']], $status);
        });
    })->create();
