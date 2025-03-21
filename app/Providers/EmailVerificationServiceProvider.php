<?php

namespace App\Providers;

use App\Listeners\LogMessageSent;
use App\Listeners\LogMessageSending;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class EmailVerificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Redirect all emails in local environment
        if ($this->app->environment('local')) {
            Mail::alwaysTo('mohamedfawzizaki@gmail.com');
        }

        // Register Event Listeners
        Event::listen(MessageSending::class, LogMessageSending::class);
        Event::listen(MessageSent::class, LogMessageSent::class);
    }
}