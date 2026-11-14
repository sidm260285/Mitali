<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBank;
use App\Http\Middleware\EnsureExecutive;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'bank' => EnsureBank::class,
            'executive' => EnsureExecutive::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(fn () => auth()->user()->dashboardRoute());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
