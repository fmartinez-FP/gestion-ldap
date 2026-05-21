<?php

namespace App\Livewire\Usuarios;

use Livewire\Component;
use App\Services\LdapService;
use App\Models\Aplicacion;
use Illuminate\Support\Facades\Mail;

class FormUsuario extends Component
{
    public string $modo        = 'crear';
    public ?string $uidOriginal = null;

    // Campos básicos
    public string $nombre    = '';
    public string $apellidos = '';
    public string $mail      = '';
    public bool   $activo    = true;
    public array  $gruposSeleccionados = [];

    // Preview (solo en crear)
    public string $uid_preview      = '';
    public string $password_preview = '';

    // Cambio de contraseña (solo en editar, opcional)
    public string $newPassword             = '';
    public string $newPasswordConfirmation = '';

    public bool   $guardado = false;
    public string $error    = '';

    protected LdapService $ldap;

    public function boot(LdapService $ldap): void
    {
        $this->ldap = $ldap;
    }

    public function mount(string $uid = null): void
    {
        if ($uid) {
            $this->modo        = 'editar';
            $this->uidOriginal = $uid;
            $user = $this->ldap->getUser($uid);
            if ($user) {
                $this->nombre              = $user['nombre'];
                $this->apellidos           = $user['apellidos'];
                $this->mail                = $user['mail'];
                $this->activo              = $user['activo'];
                $this->gruposSeleccionados = $user['grupos'];
            }
        }
    }

    public function updatedMail(): void
    {
        $this->calcularUid();
    }

    private function calcularUid(): void
    {
        if (str_contains($this->mail, '@' . env('MAIL_DOMAIN', 'micentro.es'))) {
            $this->uid_preview      = explode('@', $this->mail)[0];
            $this->password_preview = $this->uid_preview . '1234';
        } else {
            $this->uid_preview      = '';
            $this->password_preview = '';
        }
    }

    public function guardar(): void
    {
        $this->error = '';

        // Validación de campos básicos
        $this->validate([
            'nombre'    => 'required|min:2|max:100',
            'apellidos' => 'required|min:2|max:100',
            'mail'      => 'required|email|ends_with:@' . env('MAIL_DOMAIN', 'micentro.es') . '',
            'activo'    => 'boolean',
        ]);

        // Validación de contraseña (solo en editar y solo si se rellena)
        if ($this->modo === 'editar' && !empty($this->newPassword)) {
            $this->validate([
                'newPassword' => [
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[#@$!%*?\-_+=&^~]/',
                ],
            ], [
                'newPassword.min'   => 'Mínimo 8 caracteres.',
                'newPassword.regex' => 'La contraseña no cumple los requisitos de seguridad.',
            ]);

            if ($this->newPassword !== $this->newPasswordConfirmation) {
                $this->addError('newPasswordConfirmation', 'Las contraseñas no coinciden.');
                return;
            }
        }

        $uid  = $this->modo === 'crear' ? explode('@', $this->mail)[0] : $this->uidOriginal;

        $data = [
            'uid'             => $uid,
            'nombre'          => $this->nombre,
            'apellidos'       => $this->apellidos,
            'nombre_completo' => trim("{$this->nombre} {$this->apellidos}"),
            'mail'            => $this->mail,
            'activo'          => $this->activo,
            'grupos'          => $this->gruposSeleccionados,
            'password'        => $uid . '1234',
        ];

        if ($this->modo === 'crear') {
            if ($this->ldap->getUser($uid)) {
                $this->error = "El usuario '{$uid}' ya existe en el directorio.";
                return;
            }

            $ok = $this->ldap->createUser($data);

            if ($ok) {
                try {
                    Mail::send('emails.credenciales', [
                        'nombre'   => $data['nombre_completo'],
                        'username' => $uid,
                        'password' => $uid . '1234',
                        'apps'     => $this->gruposSeleccionados,
                    ], fn($m) => $m->to($data['mail'])->subject('IES Pacífico — Bienvenido al sistema'));
                } catch (\Exception) {}

                $this->guardado = true;
                $this->dispatch('usuario-guardado', uid: $uid);
            } else {
                $this->error = 'Error al crear el usuario. Revisa los logs del servidor.';
            }

        } else {
            // Editar
            unset($data['password']); // por defecto no tocar la contraseña

            // Solo incluir si el admin ha rellenado el campo
            if (!empty($this->newPassword)) {
                $data['password'] = $this->newPassword;
            }

            $ok = $this->ldap->updateUser($this->uidOriginal, $data);

            if ($ok) {
                $this->guardado            = true;
                $this->newPassword         = '';
                $this->newPasswordConfirmation = '';
                $this->dispatch('usuario-guardado', uid: $this->uidOriginal);
            } else {
                $this->error = 'Error al actualizar el usuario. Revisa los logs.';
            }
        }
    }

    public function render()
    {
        $apps = Aplicacion::where('activo', true)->orderBy('orden')->get();
        return view('livewire.usuarios.form-usuario', ['apps' => $apps]);
    }
}
