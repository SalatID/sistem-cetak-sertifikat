<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paksa HTTPS pada mode produksi (mis. di balik Cloudflare Tunnel).
 *
 * Cloudflare "Always Use HTTPS" tetap disarankan; middleware ini adalah
 * lapisan pengaman agar request HTTP tidak pernah dilayani aplikasi.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure() && app()->isProduction()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
