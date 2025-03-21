<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->remove('');
        $middleware->statefulApi();
        // 📌 Append global middleware (applies to all routes), This ensures that these middlewares are executed for every request.
        $middleware->append([
            Illuminate\Http\Middleware\ValidatePathEncoding::class,
            Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            Illuminate\Http\Middleware\TrustProxies::class,
            
            Illuminate\Http\Middleware\HandleCors::class,
            Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            Illuminate\Http\Middleware\ValidatePostSize::class,
            Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class
        ]);

        // 📌 Define middleware for the 'api' group, This middleware stack is applied only to API routes (routes/api.php)
        $middleware->group('api', [
            \App\Http\Middleware\ForceHttpsMiddleware::class,
            \App\Http\Middleware\CustomAuthenticationHandler::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,           // Resolves route model bindings
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // 'throttle:api',
        ]);

        // 📌 Define middleware for the 'web' group, This middleware stack is applied to web routes (routes/web.php)
        $middleware->group('web', [
            Illuminate\Cookie\Middleware\EncryptCookies::class,                // Encrypts cookies automatically
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,    // Handles queued cookies
            Illuminate\Session\Middleware\StartSession::class,                 // Starts the session for web users
            Illuminate\View\Middleware\ShareErrorsFromSession::class,          // Shares session errors with views   
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class            // Resolves route model bindings
        ]);
        
        // 📌 Append middleware to an existing group, If you need to dynamically add middleware to a group, use `appendToGroup`
        $middleware->appendToGroup('api', [
        ]);
        $middleware->appendToGroup('web', [
            // \App\Http\Middleware\CustomWebMiddleware::class
        ]);

        // 📌 Set predefined middleware for API and Web routes, This allows defining middleware that applies specifically to all API or Web routes
        $middleware->api([
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // 'throttle:api',
        ]);

        $middleware->web([
            // \App\Http\Middleware\EncryptCookies::class, // Encrypts cookies
        ]);

        // 📌 Middleware aliases allow using short names instead of full class names
        $middleware->alias([
            "auth"              => Illuminate\Auth\Middleware\Authenticate::class,
            "auth.basic"        => Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            "auth.session"      => Illuminate\Session\Middleware\AuthenticateSession::class,
            "cache.headers"     => Illuminate\Http\Middleware\SetCacheHeaders::class,
            "can"               => Illuminate\Auth\Middleware\Authorize::class,
            "guest"             => Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            "password.confirm"  => Illuminate\Auth\Middleware\RequirePassword::class,
            "precognitive"      => Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            "signed"            => Illuminate\Routing\Middleware\ValidateSignature::class,
            "throttle"          => Illuminate\Routing\Middleware\ThrottleRequests::class,
            "verified"          => Illuminate\Auth\Middleware\EnsureEmailIsVerified::class
        ]);

        // 📌 Middleware priority determines execution order, The first middleware in this list runs first; the last runs last.
        $middleware->priority([
            // \App\Http\Middleware\AuthMiddleware::class, // Ensures authentication before anything else
            // \App\Http\Middleware\VerifyCsrfToken::class, // Verifies CSRF tokens for security
        ]);

        // 📌 Append middleware to priority list dynamically, Useful when you want to enforce execution order for additional middleware
        // $middleware->appendToPriorityList(\App\Http\Middleware\CustomMiddleware::class, 'auth');
        $middleware->appendToPriorityList('', '');



        
    })->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();