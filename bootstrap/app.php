<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureBusinessProfile::class,
            \App\Http\Middleware\AllowFraming::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/chat/*',
            '/webhooks/threads',
            '/webhooks/instagram',
            '/webhooks/facebook',
            '/webhooks/google-business',
            '/forms/*',
            '/l/*/submit',
            '/webhooks/stripe/tenant/*',
        ]);
        $middleware->alias([
            'system-admin' => \App\Http\Middleware\SystemAdminMiddleware::class,
            'quota' => \App\Http\Middleware\EnsureWithinQuota::class,
            'allow-framing' => \App\Http\Middleware\AllowFraming::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'Session expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();
