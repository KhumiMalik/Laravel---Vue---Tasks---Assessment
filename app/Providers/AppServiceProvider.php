<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // uncompromised() calls the HIBP API, so only enforce the strict rules in production
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
