<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return "http://localhost:3000/reset-password?token=$token&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
