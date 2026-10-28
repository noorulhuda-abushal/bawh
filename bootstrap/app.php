<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 🌐 Set the site language (Arabic / English) on every page
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // 🌐 On Render the website sits behind a proxy that handles https: trust it,
        // so links and CSS files use https:// too
        $middleware->trustProxies(at: '*');

        // 🛡️ "admin" = only admins can open these pages
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ⏳ The page was open too long and its security token expired (error 419):
        // instead of an error page, go back and show a friendly message.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('The page was open for a long time. Refresh it and try again.')], 419);
            }

            return redirect()->back(fallback: route('home'))
                ->with('toast', __('The page was open for a long time. Try again 🔄'));
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
