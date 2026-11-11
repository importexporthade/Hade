<?php

use App\Http\Controllers\Store\StoredPhotoController;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Photos go out without a session or cookies, so the CDN may keep them
        then: fn () => Route::get('storage/{path}', StoredPhotoController::class)
            ->where('path', '.+')
            ->name('stored-photo'),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A signed-out request from inside a manager page goes to Livewire's own address
        // (/livewire-…/update), which cannot be opened as a page. Laravel used to remember it
        // as the page to return to, and the next sign-in showed "405 Method Not Allowed"; the
        // owner is now sent to the login without that address being remembered.
        $exceptions->render(function (AuthenticationException $exception, Request $request): ?RedirectResponse {
            if (! $request->hasHeader('X-Livewire') || ($loginUrl = $exception->redirectTo($request)) === null) {
                return null;
            }

            return redirect()->to($loginUrl);
        });
    })->create();
