<?php

namespace App\Providers;

use Illuminate\Http\Request;
use App\Contracts\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
        $this->app->bind(PaymentGateway::class, function ($app) {
            $request = $app->make(Request::class);
            $method = ucfirst(strtolower($request->query('method'))); // Ensure first letter is uppercase
            $className = "App\\Services\\{$method}PaymentGateway";

            if (!class_exists($className)) {
                throw new \InvalidArgumentException("Payment method '{$method}' not supported.");
            }

            return new $className();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}