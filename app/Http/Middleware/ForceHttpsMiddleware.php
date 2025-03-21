<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsMiddleware
{
    /**
     * Handle an incoming request and enforce HTTPS redirection in production.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance.
     * @param  \Closure  $next  The next middleware function to be executed.
     * @return \Symfony\Component\HttpFoundation\Response  The response after middleware processing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the request is NOT secure (not using HTTPS)
        // and if the application is running in production.
        if (!$request->secure() && env('APP_ENV') === 'production') {
            // Redirect to the same URL but with HTTPS enforced.
            return redirect()->secure($request->getRequestUri());
        }

        // Proceed with the request if HTTPS is already in use
        // or if the environment is not set to production.
        return $next($request);
    }
}