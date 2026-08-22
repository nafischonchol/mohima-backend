<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('customer')
                ->name('customer.')
                ->group(base_path('routes/Customer/public.php'));

            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('customer/me')
                ->name('customer.me.')
                ->group(base_path('routes/Customer/api.php'));

            Route::middleware('api')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/Admin/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
