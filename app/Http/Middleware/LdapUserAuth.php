<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LdapUserAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('ldap_user')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
