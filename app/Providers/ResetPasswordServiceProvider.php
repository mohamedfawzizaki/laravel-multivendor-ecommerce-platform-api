<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordServiceProvider extends ServiceProvider
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
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            // return 'https://front.example.com/reset-password?token='.$token.'&'.$user->email;
            return config('auth.passwords.link').'?token='.$token.'&email='.$user->email;
        });
    }
}