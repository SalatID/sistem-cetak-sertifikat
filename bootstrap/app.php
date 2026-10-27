<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\ForceHttps;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);

        // Di balik Cloudflare Tunnel semua permintaan datang dari IP Cloudflare.
        // Percayai seluruh proxy agar Laravel membaca IP asli pengguna dari
        // header X-Forwarded-For (rate limiter & cookie secure bekerja benar).
        $middleware->trustProxies(at: '*');

        // Pengaman tambahan: pada mode produksi, request HTTP dialihkan ke HTTPS.
        // Cloudflare "Always Use HTTPS" tetap disarankan sebagai lapisan utama.
        $middleware->web(append: [
            ForceHttps::class,
        ]);

        // Tamu yang membuka halaman utama diarahkan ke halaman cetak publik,
        // bukan ke form login. Halaman admin lain tetap diarahkan ke /login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('/')
            ? route('publik.index')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
