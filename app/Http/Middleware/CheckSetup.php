<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        // Rutas que no deben ser interceptadas aunque no esté configurado
        if ($request->routeIs('setup', 'setup.*', 'login', 'login.post', 'logout')) {
            return $next($request);
        }

        if (! Setting::isConfigured()) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
