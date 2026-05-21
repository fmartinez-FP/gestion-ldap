<?php

namespace App\Http\Controllers;

use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }
        if (session()->has('ldap_user')) {
            return redirect()->route('portal.dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');

        // ── 1. Intento admin (tabla admins, guard Eloquent) ───────────────
        if (Auth::guard('admin')->attempt(['username' => $username, 'password' => $password])) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // ── 2. Intento usuario LDAP (bind directo, sin LdapRecord) ────────
        try {
            $ldapUser = LdapService::authenticateUser($username, $password);

            if ($ldapUser !== null) {
                $request->session()->regenerate();
                session(['ldap_user' => $ldapUser]);

                // ppolicy: si el admin resetó la contraseña, forzar cambio
                // antes de permitir acceso al portal
                if (LdapService::hasPwdReset($ldapUser['uid'])) {
                    session(['ldap_must_change_password' => true]);
                    return redirect()->route('portal.password');
                }

                return redirect()->route('portal.dashboard');
            }
        } catch (\RuntimeException $e) {
            Log::error('Portal LDAP login error: ' . $e->getMessage());
            // No exponer detalle del error al usuario
        }

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Usuario o contraseña incorrectos.']);
    }

    public function logout(Request $request)
    {
        // Cierra sesión para ambos tipos de usuario
        Auth::guard('admin')->logout();
        session()->forget('ldap_user');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
