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
        // Batas cetak untuk tamu (tanpa login): 30 berkas PDF per jam per IP.
        RateLimiter::for('cetak-publik', function (Request $request) {
            return Limit::perHour(30)->by($request->ip());
        });

        // Batas permintaan umum untuk halaman tamu: 60 permintaan per menit per IP.
        RateLimiter::for('publik', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
