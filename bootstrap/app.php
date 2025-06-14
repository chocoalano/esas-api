<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias middleware untuk penggunaan di route
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'mac.restrict' => \App\Http\Middleware\MacAddressMiddleware::class,
        ]);
        // Tangani redirect untuk guest pada API routes
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return route('unauthorized');
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
