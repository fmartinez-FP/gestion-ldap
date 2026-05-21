<?php

namespace App\Http\Controllers;

use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LdapPasswordResetController extends Controller
{
    // Mensaje genérico para no revelar si el usuario existe
    private const GENERIC_MSG = 'Si el usuario existe y tiene correo registrado, recibirás un enlace en breve.';

    /** Formulario: introducir nombre de usuario */
    public function showRequestForm()
    {
        return view('auth.passwords.ldap-email');
    }

    /** Genera el token, lo guarda y envía el email */
    public function sendResetLink(Request $request)
    {
        $request->validate(
            ['username' => ['required', 'string', 'max:100']],
            ['username.required' => 'El nombre de usuario es obligatorio.']
        );

        $uid = trim($request->input('username'));

        try {
            $ldap = new LdapService();
            $user = $ldap->getUser($uid);

            // Salir silenciosamente si no existe, está inactivo o no tiene email
            if (!$user || !$user['activo'] || empty($user['mail'])) {
                return back()->with('status', self::GENERIC_MSG);
            }

            // Token aleatorio de 64 caracteres; se guarda hasheado
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user['mail']],
                [
                    'email'      => $user['mail'],
                    'token'      => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetUrl = url(route('ldap.password.reset', [
                'token' => $token,
                'email' => $user['mail'],
            ], false));

            Mail::send(
                'emails.ldap-password-reset',
                ['resetUrl' => $resetUrl, 'nombreCompleto' => $user['nombre_completo']],
                fn($m) => $m->to($user['mail'])->subject('Restablecer contraseña — IES Pacífico')
            );
        } catch (\Exception $e) {
            Log::error('LdapPasswordReset sendResetLink: ' . $e->getMessage());
            // No exponer el error; el mensaje genérico es suficiente
        }

        return back()->with('status', self::GENERIC_MSG);
    }

    /** Formulario: introducir nueva contraseña */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.passwords.ldap-reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /** Verifica el token y aplica la nueva contraseña en LDAP */
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => [
                'required', 'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[#@$!%*?\-_+=&^~]/',
                'confirmed',
            ],
        ], [
            'password.required'  => 'La nueva contraseña es obligatoria.',
            'password.min'       => 'Mínimo 8 caracteres.',
            'password.regex'     => 'La contraseña no cumple los requisitos de seguridad.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Verificar token en DB
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'El enlace no es válido o ya ha sido utilizado.']);
        }

        // Verificar caducidad (60 minutos)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'El enlace ha caducado. Solicita uno nuevo.']);
        }

        // Aplicar la nueva contraseña en LDAP
        try {
            $ldap = new LdapService();
            $user = $ldap->getUserByMail($request->email);

            if (!$user) {
                return back()->withErrors(['email' => 'No se encontró el usuario en el directorio LDAP.']);
            }

            if (!$ldap->setPassword($user['uid'], $request->password)) {
                return back()->withErrors(['email' => 'Error al actualizar la contraseña. Contacta con el administrador.']);
            }
        } catch (\Exception $e) {
            Log::error('LdapPasswordReset reset: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Error de conexión con el servidor LDAP.']);
        }

        // Eliminar token ya usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('status', '✓ Contraseña restablecida correctamente. Ya puedes iniciar sesión.');
    }
}
