<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Brute-force guard for both login screens: 10 tries a minute per email + IP
        // (per email, not just IP, so a whole school behind one NAT is not locked out).
        // Defined here, not in routes/api.php: with `route:cache` the routes file is
        // never executed, and the limiter would be missing (every login a 500).
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
    }
}
