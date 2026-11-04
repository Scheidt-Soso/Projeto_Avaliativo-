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
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('guildas.dashboard'));
        $middleware->trimStrings(except: ['senha', 'senha_confirmation']);
        $middleware->statefulApi();
        $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['senha', 'senha_confirmation']);
        $exceptions->render(function (\Illuminate\Database\QueryException $exception, \Illuminate\Http\Request $request) {
            $message = \App\Support\DatabaseError::message($exception);
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['banco' => [$message]]], \App\Support\DatabaseError::isValidation($exception) ? 422 : 503);
            }
            if (! $request->isMethod('GET') && \App\Support\DatabaseError::isValidation($exception)) {
                return back()->withErrors(['banco' => $message])->withInput($request->except(['senha', 'senha_confirmation', '_token']));
            }
        });
    })->create();
