<?php

// ============================================================================
// bootstrap/app.php — the Laravel application's BOOTSTRAP / entry-point setup.
// This is where the framework is "configured" when it first starts up.
//
// Think of it as the front door of a building: it assembles the app, tells it
// where the routes live, registers custom middleware aliases, and configures
// how exceptions (errors) are handled.
// ============================================================================

// Imports: core Laravel classes.
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Create and configure the Laravel application.
return Application::configure(basePath: dirname(__DIR__)) // basePath = the project root (folder above /bootstrap)
    // Tell Laravel where to find its routes.
    ->withRouting(
        web: __DIR__.'/../routes/web.php',      // browser (HTTP) routes
        commands: __DIR__.'/../routes/console.php', // CLI (artisan) commands
        health: '/up',                          // a special URL that reports "health" (alive check)
    )
    // Register custom middleware.
    ->withMiddleware(function (Middleware $middleware): void {
        // Create a shortcut ALIAS called 'role'.
        // Now in routes we can write: Route::middleware('role:products') ...
        // ...which actually uses the RoleMiddleware class we defined.
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    // Configure how exceptions (unexpected errors) are handled.
    ->withExceptions(function (Exceptions $exceptions): void {
        // Only return JSON errors when the request is an API call or explicitly
        // expects JSON. Normal browser requests get the HTML error page instead.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create(); // finally, build the Application object and return it
